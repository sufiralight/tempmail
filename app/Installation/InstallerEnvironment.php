<?php

namespace App\Installation;

use Dotenv\Dotenv;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use RuntimeException;

/** @phpstan-import-type Settings from InstallerDatabase */
class InstallerEnvironment
{
    /** @var list<string> */
    public const KEYS = [
        'APP_KEY', 'APP_ENV', 'APP_DEBUG', 'APP_URL', 'DB_CONNECTION', 'DB_URL', 'DB_SOCKET',
        'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_INSTALLER_LITERAL',
    ];

    public static function captureExternalValues(): void
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $value = Env::getRepository()->get($key);
            if ($value !== null) {
                $values[$key] = hash('sha256', $value);
            }
        }

        app()->instance('installer.external_environment', $values);
    }

    /**
     * @param  Settings  $database
     * @return array{path: string, previous: ?string, contents: string}
     */
    public function prepare(Request $request, #[\SensitiveParameter] array $database): array
    {
        $path = app()->environmentFilePath();
        $directory = realpath(dirname($path));
        $public = realpath(public_path());
        if (app()->configurationIsCached() || basename($path) !== '.env' || is_link($path)
            || (is_file($path) && ! is_writable($path))
            || $directory === false || $public === false || ! is_writable($directory)
            || str_starts_with(strtolower($directory.DIRECTORY_SEPARATOR), strtolower($public.DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Environment file cannot be managed by the installer.');
        }

        $previous = $this->read($path);
        $existing = Dotenv::parse($previous ?? '');
        $original = $request->attributes->get('installer.original_config');
        $cipher = $original['app.cipher'];
        $key = $original['app.key'] ?: ($existing['APP_KEY'] ?? '');
        $rawKey = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;

        if ($key === '') {
            $key = 'base64:'.base64_encode(Encrypter::generateKey($cipher));
        } elseif (! is_string($rawKey) || ! Encrypter::supported($rawKey, $cipher)
            || (! empty($existing['APP_KEY']) && $existing['APP_KEY'] !== $key)) {
            throw new RuntimeException('The existing application key requires operator review.');
        }

        $local = $request->attributes->get('installer.access')['local'];
        $engine = DatabaseEngine::from($database['engine'] ?? 'mysql');
        $values = [
            'APP_KEY' => $key,
            'APP_ENV' => $local ? ($existing['APP_ENV'] ?? 'local') : 'production',
            'APP_URL' => $local ? ($existing['APP_URL'] ?? $request->getSchemeAndHttpHost()) : 'https://'.$request->getHost(),
            'DB_CONNECTION' => $engine->value,
            'DB_URL' => '',
            'DB_SOCKET' => '',
            'DB_HOST' => $database['host'],
            'DB_PORT' => (string) $database['port'],
            'DB_DATABASE' => $engine === DatabaseEngine::Sqlite ? app(InstallerDatabase::class)->sqlitePath() : $database['database'],
            'DB_USERNAME' => $database['username'],
            'DB_PASSWORD' => $database['password'],
            'DB_INSTALLER_LITERAL' => 'true',
        ];

        if (! $local || ! array_key_exists('APP_DEBUG', $existing)) {
            $values['APP_DEBUG'] = 'false';
        }

        $external = app()->bound('installer.external_environment') ? app('installer.external_environment') : [];
        foreach ($values as $name => $value) {
            if (isset($external[$name]) && $external[$name] !== hash('sha256', $value)) {
                throw new RuntimeException('A process environment variable conflicts with installation.');
            }
        }

        $contents = $this->update($previous ?? '', $existing, $values);

        return compact('path', 'previous', 'contents');
    }

    /** @param array{path: string, previous: ?string, contents: string} $environment */
    public function persist(#[\SensitiveParameter] array $environment): void
    {
        if ($this->read($environment['path']) !== $environment['previous']) {
            throw new RuntimeException('Environment changed while installation was in progress.');
        }

        PrivateInstallationFile::replace($environment['path'], $environment['contents']);
    }

    private function read(string $path): ?string
    {
        clearstatcache(true, $path);
        if (! file_exists($path) && ! is_link($path)) {
            return null;
        }

        $contents = ! is_link($path) && is_file($path) ? @file_get_contents($path, length: 1048577) : false;
        if ($contents === false || strlen($contents) > 1048576) {
            throw new RuntimeException('Environment file is unavailable.');
        }

        return $contents;
    }

    /** @param array<string, string> $existing @param array<string, string> $values */
    private function update(#[\SensitiveParameter] string $contents, #[\SensitiveParameter] array $existing, #[\SensitiveParameter] array $values): string
    {
        $eol = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $keys = implode('|', array_keys($values));
        $contents = preg_replace('/^[\t ]*(?:export[\t ]+)?(?:'.$keys.')[\t ]*=[^\r\n]*(?:\r?\n|$)/m', '', $contents);
        $contents .= $contents === '' || str_ends_with($contents, "\n") ? '' : $eol;

        foreach ($values as $key => $value) {
            $quoted = strtr($value, ['\\' => '\\\\', '"' => '\\"', '$' => '\\$', "\r" => '\r', "\n" => '\n', "\t" => '\t']);
            $contents .= $key.'="'.$quoted.'"'.$eol;
        }

        $parsed = Dotenv::parse($contents);
        if (array_intersect_key($parsed, $values) !== $values
            || array_diff_key($parsed, $values) !== array_diff_key($existing, $values)) {
            throw new RuntimeException('Environment changes would alter unrelated configuration.');
        }

        return $contents;
    }
}
