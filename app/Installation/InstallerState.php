<?php

namespace App\Installation;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class InstallerState
{
    public const LIFETIME_MINUTES = 15;

    public function __construct(private Filesystem $files, private InstallationAccess $access) {}

    public function bootstrapKey(): string
    {
        return $this->locked(function (string $directory): string {
            if ($this->access->installed()) {
                abort(404);
            }

            $path = $directory.'/key';
            if (! is_file($path)) {
                if (file_exists($directory.'/lease') || is_link($path)) {
                    throw new RuntimeException('Installer key is missing.');
                }

                $this->writePrivate($path, base64_encode(random_bytes(32)));
            }

            if (is_link($path)) {
                throw new RuntimeException('Installer key is invalid.');
            }

            $key = $this->files->get($path);
            if (strlen((string) base64_decode($key, true)) !== 32) {
                throw new RuntimeException('Installer key is invalid.');
            }

            return 'base64:'.$key;
        });
    }

    public function beginVerification(Request $request): string
    {
        return $this->locked(function (string $directory) use ($request): string {
            $scope = $this->scope($request);
            if ($this->pendingFinalization()) {
                throw new InstallationRecoveryRequired;
            }

            $owner = $request->session()->get('installer.owner');
            $lease = $this->activeLease($directory, $scope);

            if ($lease !== null && ! $this->owns($lease, $owner)) {
                throw new ConflictHttpException('Another installation session is active. Try again when it expires.');
            }

            if ($lease === null) {
                $owner = bin2hex(random_bytes(32));
                $request->session()->regenerate(true);
                $request->session()->put('installer.owner', $owner);
                Log::notice('Installer ownership established.');
            }

            $attempt = bin2hex(random_bytes(16));
            $this->writeLease($directory, [
                'scope' => $scope,
                'owner' => hash('sha256', (string) $owner),
                'expires' => $lease['expires'] ?? now()->addMinutes(self::LIFETIME_MINUTES)->timestamp,
                'attempt' => $attempt,
                'database' => null,
            ]);

            $request->session()->forget('installer.verified');

            return $attempt;
        });
    }

    /** @param array{host: string, port: int, database: string, username: string, password: string} $database */
    public function completeVerification(Request $request, string $attempt, #[\SensitiveParameter] array $database): void
    {
        $cipher = $this->cipher();
        $this->locked(function (string $directory) use ($request, $attempt, $database, $cipher): void {
            $lease = $this->activeLease($directory, $this->scope($request));

            if ($lease === null || ! $this->owns($lease, $request->session()->get('installer.owner')) || $lease['attempt'] !== $attempt) {
                throw new ConflictHttpException('Installation verification expired or was replaced. Verify the database again.');
            }

            $lease['database'] = $cipher->encryptString(json_encode($database, JSON_THROW_ON_ERROR));
            $this->writeLease($directory, $lease);
            $request->session()->regenerate(true);
            $request->session()->put('installer.verified', true);
            Log::notice('Installer database verification completed.');
        });
    }

    public function isVerified(Request $request): bool
    {
        return $this->verifiedPayload($request) !== null;
    }

    public function pendingFinalization(): bool
    {
        $path = config('installer.path').'/finalization';
        clearstatcache(true, $path);

        return file_exists($path) || is_link($path);
    }

    /**
     * @param  Closure(string, array{host: string, port: int, database: string, username: string, password: string}): void  $finish
     */
    public function finalize(Request $request, Closure $finish): void
    {
        $cipher = $this->cipher();
        $this->locked(function (string $directory) use ($request, $finish, $cipher): void {
            $lease = $this->activeLease($directory, $this->scope($request));
            if ($lease === null || $lease['database'] === null || ! $this->owns($lease, $request->session()->get('installer.owner'))) {
                abort(404);
            }

            $database = json_decode($cipher->decryptString($lease['database']), true, flags: JSON_THROW_ON_ERROR);
            $finish($directory, $database);
            $this->writePrivate($directory.'/installed', now()->toIso8601String());

            foreach (['lease', 'key', 'finalization', 'enable.json'] as $file) {
                $path = $directory.'/'.$file;
                if ((is_file($path) || is_link($path)) && ! @unlink($path)) {
                    Log::warning('Installer temporary file cleanup requires operator attention.');
                }
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        });
    }

    /** @return array{host: string, port: int, database: string, username: string, password: string}|null */
    public function verifiedDatabase(Request $request): ?array
    {
        $payload = $this->verifiedPayload($request);

        return $payload === null ? null : json_decode($this->cipher()->decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
    }

    private function verifiedPayload(Request $request): ?string
    {
        return $this->locked(function (string $directory) use ($request): ?string {
            $lease = $this->activeLease($directory, $this->scope($request));

            return $lease !== null && $this->owns($lease, $request->session()->get('installer.owner')) ? $lease['database'] : null;
        });
    }

    private function cipher(): Encrypter
    {
        $key = base64_decode(substr($this->bootstrapKey(), 7), true);

        return new Encrypter((string) $key, 'AES-256-CBC');
    }

    private function scope(Request $request): string
    {
        $current = $this->access->forRequest($request);

        if ($current === null || $current['scope'] !== $request->attributes->get('installer.access')['scope']) {
            throw new ConflictHttpException('Installation authorization changed. Return to setup.');
        }

        return $current['scope'];
    }

    /** @param array{scope: string, owner: string, expires: int, attempt: string, database: ?string} $lease */
    private function writeLease(string $directory, array $lease): void
    {
        $this->writePrivate($directory.'/lease', json_encode($lease, JSON_THROW_ON_ERROR));
    }

    private function writePrivate(string $path, string $contents): void
    {
        PrivateInstallationFile::replace($path, $contents);
    }

    /** @return array{scope: string, owner: string, expires: int, attempt: string, database: ?string}|null */
    private function activeLease(string $directory, string $scope): ?array
    {
        $path = $directory.'/lease';
        if (! is_file($path) && ! is_link($path)) {
            return null;
        }

        if (is_link($path)) {
            throw new RuntimeException('Installer lease is invalid.');
        }

        $lease = json_decode($this->files->get($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($lease) || ! is_string($lease['scope'] ?? null) || ! is_string($lease['owner'] ?? null)
            || ! is_int($lease['expires'] ?? null) || ! is_string($lease['attempt'] ?? null)
            || ! array_key_exists('database', $lease) || ($lease['database'] !== null && ! is_string($lease['database']))) {
            throw new RuntimeException('Installer lease is invalid.');
        }

        if ($lease['expires'] <= now()->timestamp || $lease['scope'] !== $scope) {
            if (! unlink($path)) {
                throw new RuntimeException('Installer lease could not be cleared.');
            }

            return null;
        }

        return $lease;
    }

    /** @param array{owner: string} $lease */
    private function owns(array $lease, mixed $owner): bool
    {
        return is_string($owner) && hash_equals($lease['owner'], hash('sha256', $owner));
    }

    /**
     * @template T
     *
     * @param  Closure(string): T  $callback
     * @return T
     */
    private function locked(Closure $callback): mixed
    {
        $directory = config('installer.path');
        $path = $directory.'/lock';

        if (is_link($directory) || ! is_dir($directory) || is_link($path)) {
            throw new ServiceUnavailableHttpException(message: 'Private installation storage is unavailable.');
        }

        $lock = @fopen($path, 'c+');
        if ($lock === false) {
            throw new ServiceUnavailableHttpException(message: 'Private installation storage is unavailable.');
        }

        try {
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                throw new ServiceUnavailableHttpException(1, 'Installation is busy. Try again.');
            }

            return $callback($directory);
        } catch (RuntimeException|FileNotFoundException|JsonException $exception) {
            if ($exception instanceof HttpExceptionInterface) {
                throw $exception;
            }

            throw new ServiceUnavailableHttpException(message: 'Private installation storage is unavailable. Contact the deployment operator.');
        } finally {
            fclose($lock);
        }
    }
}
