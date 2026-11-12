<?php

namespace App\Installation;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use PDO;
use Pdo\Mysql;
use RuntimeException;

/**
 * @phpstan-type Settings array{engine?: string, host: string, port: int, database: string, username: string, password: string}
 */
final class InstallerDatabase
{
    public function __construct(private DatabaseManager $databases) {}

    /** @param Settings $settings */
    public function connect(string $name, #[\SensitiveParameter] array $settings, bool $verification = false): Connection
    {
        $engine = DatabaseEngine::from($settings['engine'] ?? 'mysql');
        if (! $engine->available()) {
            throw new RuntimeException('Required database driver is unavailable.');
        }

        $config = match ($engine) {
            DatabaseEngine::Sqlite => [
                'database' => $verification ? ':memory:' : $this->sqlitePath(),
                'foreign_key_constraints' => true, 'busy_timeout' => 3000,
            ],
            DatabaseEngine::MySql, DatabaseEngine::MariaDb => [
                'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
                'strict' => true, 'engine' => 'InnoDB', 'options' => $this->mysqlOptions($engine),
            ],
            DatabaseEngine::PostgreSql => [
                'charset' => 'utf8', 'search_path' => 'public',
                'sslmode' => config('database.connections.pgsql.sslmode', 'prefer'),
                'options' => [PDO::ATTR_TIMEOUT => 3],
            ],
            DatabaseEngine::SqlServer => [
                'charset' => 'utf8', 'login_timeout' => 3,
                'pooling' => false,
                'encrypt' => config('database.connections.sqlsrv.encrypt', 'yes'),
                'trust_server_certificate' => config('database.connections.sqlsrv.trust_server_certificate', 'no'),
            ],
        };

        $connection = $this->databases->connectUsing($name, $config + $settings + [
            'driver' => $engine->value, 'prefix' => '',
        ]);

        if (! $connection instanceof Connection) {
            throw new RuntimeException('Unexpected installation connection.');
        }

        $connection->setReconnector(static fn () => throw new RuntimeException('Installation connection was interrupted.'));

        return $connection;
    }

    public function close(string $name): void
    {
        $this->databases->purge($name);
    }

    public function sqlitePath(): string
    {
        $directory = config('installer.path');
        $parent = realpath($directory);
        $public = realpath(public_path());
        $normalize = static function (string $path): string {
            $path = rtrim(str_replace('\\', '/', $path), '/');

            return DIRECTORY_SEPARATOR === '\\' ? strtolower($path) : $path;
        };
        if (is_link($directory) || $parent === false || $public === false || ! is_writable($parent)
            || $normalize($parent) !== $normalize($directory)
            || str_starts_with($normalize($parent).'/', $normalize($public).'/')) {
            throw new RuntimeException('Private database storage is unavailable.');
        }

        return $parent.DIRECTORY_SEPARATOR.'workspace.sqlite';
    }

    public function sqliteFileAvailable(bool $fresh): bool
    {
        $path = $this->sqlitePath();
        clearstatcache(true, $path);

        return ! is_link($path) && ($fresh ? ! file_exists($path) : is_file($path));
    }

    public function reserveSqlite(): void
    {
        $path = $this->sqlitePath();
        $handle = @fopen($path, 'xb');
        if ($handle === false) {
            throw new InstallationRecoveryRequired;
        }

        try {
            if (DIRECTORY_SEPARATOR !== '\\' && ! @chmod($path, 0600)) {
                throw new RuntimeException('Private database permissions could not be set.');
            }
        } finally {
            fclose($handle);
        }
    }

    public function matchesTarget(Connection $connection, string $database): bool
    {
        return match ($connection->getDriverName()) {
            'sqlite' => true,
            'mysql', 'mariadb' => $connection->scalar('SELECT DATABASE()', [], false) === $database,
            'pgsql' => $connection->scalar('SELECT current_database()', [], false) === $database
                && $connection->scalar('SELECT current_schema()', [], false) === 'public',
            'sqlsrv' => $connection->scalar('SELECT DB_NAME()', [], false) === $database
                && $connection->scalar('SELECT SCHEMA_NAME()', [], false) === 'dbo',
            default => false,
        };
    }

    public function empty(Connection $connection): bool
    {
        $sql = match ($connection->getDriverName()) {
            'mysql', 'mariadb' => [
                'SHOW FULL TABLES',
                'SELECT 1 FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() LIMIT 1',
                'SELECT 1 FROM information_schema.EVENTS WHERE EVENT_SCHEMA = DATABASE() LIMIT 1',
            ],
            'pgsql' => [
                "SELECT 1 FROM pg_catalog.pg_namespace WHERE nspname NOT IN ('public', 'pg_catalog', 'information_schema') AND left(nspname, 3) <> 'pg_' LIMIT 1",
                "SELECT 1 FROM pg_catalog.pg_class c JOIN pg_catalog.pg_namespace n ON c.relnamespace = n.oid WHERE n.nspname NOT IN ('pg_catalog', 'information_schema') AND left(n.nspname, 3) <> 'pg_' LIMIT 1",
                "SELECT 1 FROM pg_catalog.pg_proc p JOIN pg_catalog.pg_namespace n ON p.pronamespace = n.oid WHERE n.nspname NOT IN ('pg_catalog', 'information_schema') AND left(n.nspname, 3) <> 'pg_' LIMIT 1",
            ],
            'sqlsrv' => ['SELECT TOP (1) 1 FROM sys.objects WHERE is_ms_shipped = 0'],
            'sqlite' => ["SELECT 1 FROM sqlite_schema WHERE substr(name, 1, 7) <> 'sqlite_' LIMIT 1"],
            default => throw new RuntimeException('Unsupported installation connection.'),
        };

        foreach ($sql as $statement) {
            if ($connection->selectOne($statement, [], false) !== null) {
                return false;
            }
        }

        return true;
    }

    public function lock(Connection $connection, string $database): bool
    {
        $key = 'tempmail:'.substr(hash('sha256', strtolower($database)), 0, 48);

        return match ($connection->getDriverName()) {
            'sqlite' => true, // The private filesystem lock covers the only permitted file.
            'mysql', 'mariadb' => (int) $connection->scalar('SELECT GET_LOCK(?, 0)', [$key], false) === 1,
            'pgsql' => (int) $connection->scalar('SELECT CASE WHEN pg_try_advisory_lock(hashtext(?)) THEN 1 ELSE 0 END', [$key], false) === 1,
            'sqlsrv' => (int) $connection->scalar(
                "SET NOCOUNT ON; DECLARE @result int; EXEC @result = sys.sp_getapplock @Resource = ?, @LockMode = 'Exclusive', @LockOwner = 'Session', @LockTimeout = 0; SELECT CASE WHEN @result >= 0 THEN 1 ELSE 0 END;",
                [$key], false,
            ) === 1,
            default => false,
        };
    }

    /** @return array<int, mixed> */
    private function mysqlOptions(DatabaseEngine $engine): array
    {
        $options = [PDO::ATTR_TIMEOUT => 3, Mysql::ATTR_LOCAL_INFILE => false, Mysql::ATTR_MULTI_STATEMENTS => false];
        $ca = config('database.connections.'.$engine->value.'.options')[Mysql::ATTR_SSL_CA] ?? null;

        if (is_string($ca) && $ca !== '') {
            $options[Mysql::ATTR_SSL_CA] = $ca;
            $options[Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        return $options;
    }
}
