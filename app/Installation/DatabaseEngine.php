<?php

namespace App\Installation;

use PDO;

enum DatabaseEngine: string
{
    case MySql = 'mysql';
    case MariaDb = 'mariadb';
    case PostgreSql = 'pgsql';
    case Sqlite = 'sqlite';
    case SqlServer = 'sqlsrv';

    public function label(): string
    {
        return match ($this) {
            self::MySql => 'MySQL',
            self::MariaDb => 'MariaDB',
            self::PostgreSql => 'PostgreSQL',
            self::Sqlite => 'SQLite',
            self::SqlServer => 'Microsoft SQL Server',
        };
    }

    public function port(): ?int
    {
        return match ($this) {
            self::MySql, self::MariaDb => 3306,
            self::PostgreSql => 5432,
            self::SqlServer => 1433,
            self::Sqlite => null,
        };
    }

    public function extension(): string
    {
        return 'pdo_'.($this === self::MariaDb ? 'mysql' : $this->value);
    }

    public function available(): bool
    {
        $driver = $this === self::MariaDb ? 'mysql' : $this->value;

        return extension_loaded($this->extension()) && in_array($driver, PDO::getAvailableDrivers(), true);
    }
}
