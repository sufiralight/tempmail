<?php

namespace App\Installation;

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/** @phpstan-import-type Settings from InstallerDatabase */
final class InstallerFinalizer
{
    private const CONNECTION = 'installer_finalization';

    public function __construct(
        private InstallerState $state,
        private InstallerEnvironment $environment,
        private InstallerDatabase $database,
        private Migrator $migrator,
    ) {}

    /** @param array{name: string, email: string, password: string} $administrator */
    public function finish(Request $request, #[\SensitiveParameter] array $administrator): void
    {
        try {
            $this->state->finalize($request, function (string $directory, #[\SensitiveParameter] array $database) use ($request, $administrator): void {
                $this->install($request, $directory, $database, $administrator);
            });
        } catch (Throwable $exception) {
            Log::warning($exception instanceof InstallationRecoveryRequired ? 'Installer recovery required.' : 'Installer finalization did not complete.');

            throw $exception;
        }

        Log::notice('Workspace installation finalized.');
    }

    /**
     * @param  Settings  $settings
     * @param  array{name: string, email: string, password: string}  $administrator
     */
    private function install(Request $request, string $directory, #[\SensitiveParameter] array $settings, #[\SensitiveParameter] array $administrator): void
    {
        $environment = $this->environment->prepare($request, $settings);
        $path = $directory.'/finalization';
        $identity = [$settings['host'], $settings['port'], $settings['database'], $settings['username']];
        $engine = DatabaseEngine::from($settings['engine'] ?? 'mysql');
        if ($engine !== DatabaseEngine::MySql) {
            $identity[] = $engine->value;
        }
        $target = hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR));
        $checkpoint = $this->checkpoint($path, $target);
        $fresh = $checkpoint === null;

        if ($engine === DatabaseEngine::Sqlite) {
            if (! $this->database->sqliteFileAvailable($fresh)) {
                throw new InstallationRecoveryRequired;
            }

            if ($fresh) {
                $checkpoint = $this->startCheckpoint($path, $target);
                $this->database->reserveSqlite();
            }
        }

        try {
            $connection = $this->database->connect(self::CONNECTION, $settings);
            if (! $this->database->matchesTarget($connection, $settings['database'])) {
                throw new InstallationRecoveryRequired;
            }

            if (! $this->database->lock($connection, $settings['database'])) {
                abort(409);
            }

            if ($fresh) {
                if (! $this->database->empty($connection)) {
                    throw new InstallationRecoveryRequired;
                }
                $checkpoint ??= $this->startCheckpoint($path, $target);
                Log::notice('Installer migration started.');

                $this->migrator->usingConnection(self::CONNECTION, function (): void {
                    $this->migrator->getRepository()->createRepository();
                    $this->migrator->run([database_path('migrations')]);
                });

                Log::notice('Installer migration completed.');
            }

            $this->requireCompletedMigrations();
            $this->provision($connection, $checkpoint['operation'], $administrator, $fresh);
            $checkpoint['stage'] = 'account_created';
            $this->saveCheckpoint($path, $checkpoint);
            $this->environment->persist($environment);
        } finally {
            $this->database->close(self::CONNECTION);
        }
    }

    /** @return array{target: string, operation: string, stage: string} */
    private function startCheckpoint(string $path, string $target): array
    {
        $checkpoint = ['target' => $target, 'operation' => (string) Str::uuid(), 'stage' => 'migrating'];
        $this->saveCheckpoint($path, $checkpoint);

        return $checkpoint;
    }

    private function requireCompletedMigrations(): void
    {
        $this->migrator->usingConnection(self::CONNECTION, function (): void {
            $expected = array_keys($this->migrator->getMigrationFiles([database_path('migrations')]));
            if (! $this->migrator->repositoryExists() || $this->migrator->getRepository()->getRan() !== $expected) {
                throw new InstallationRecoveryRequired;
            }
        });
    }

    /** @param array{name: string, email: string, password: string} $administrator */
    private function provision(Connection $connection, string $operation, #[\SensitiveParameter] array $administrator, bool $fresh): void
    {
        $created = $connection->transaction(function () use ($connection, $operation, $administrator, $fresh): bool {
            $installation = $connection->table('workspace_installation')->where('id', 1)->first();
            $users = $connection->table('users');

            if ($installation !== null) {
                $admin = $users->find($installation->initial_admin_id);
                if ($installation->operation_id !== $operation || ! is_object($admin) || ! $admin->is_super_admin
                    || $admin->email !== $administrator['email'] || ! Hash::check($administrator['password'], $admin->password)) {
                    throw new InstallationRecoveryRequired;
                }

                return false;
            }

            if (! $fresh || $users->exists() || $connection->table('workspace_installation')->exists()) {
                throw new InstallationRecoveryRequired;
            }

            $id = $users->insertGetId([
                'name' => $administrator['name'],
                'email' => $administrator['email'],
                'password' => Hash::make($administrator['password']),
                'is_super_admin' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $connection->table('workspace_installation')->insert([
                'id' => 1,
                'operation_id' => $operation,
                'initial_admin_id' => $id,
                'completed_at' => now(),
            ]);

            return true;
        });

        if ($created) {
            Log::notice('Initial Super Admin provisioned.');
        }
    }

    /** @return array{target: string, operation: string, stage: string}|null */
    private function checkpoint(string $path, string $target): ?array
    {
        if (! file_exists($path) && ! is_link($path)) {
            return null;
        }

        $contents = ! is_link($path) && is_file($path) ? @file_get_contents($path, length: 4097) : false;
        $checkpoint = is_string($contents) ? json_decode($contents, true) : null;

        if (! is_array($checkpoint) || ($checkpoint['target'] ?? null) !== $target
            || ! in_array($checkpoint['stage'] ?? null, ['migrating', 'account_created'], true)
            || ! is_string($checkpoint['operation'] ?? null) || ! Str::isUuid($checkpoint['operation'])) {
            throw new InstallationRecoveryRequired;
        }

        return $checkpoint;
    }

    /** @param array{target: string, operation: string, stage: string} $checkpoint */
    private function saveCheckpoint(string $path, array $checkpoint): void
    {
        PrivateInstallationFile::replace($path, json_encode($checkpoint, JSON_THROW_ON_ERROR));
    }
}
