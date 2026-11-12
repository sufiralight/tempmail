<?php

namespace App\Installation;

use PDO;
use Pdo\Mysql;
use PDOException;

class DatabaseVerifier
{
    /** @param array{host: string, port: int, database: string, username: string, password: string} $settings */
    public function verify(#[\SensitiveParameter] array $settings): bool
    {
        if (! extension_loaded('pdo_mysql')) {
            return false;
        }

        $options = [
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            Mysql::ATTR_LOCAL_INFILE => false,
            Mysql::ATTR_MULTI_STATEMENTS => false,
        ];

        $ca = config('database.connections.mysql.options')[Mysql::ATTR_SSL_CA] ?? null;
        if (is_string($ca) && $ca !== '') {
            $options[Mysql::ATTR_SSL_CA] = $ca;
            $options[Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        $previousReadTimeout = ini_set('mysqlnd.net_read_timeout', '3');

        try {
            $connection = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $settings['host'], $settings['port'], $settings['database']),
                $settings['username'],
                $settings['password'],
                $options,
            );

            return (int) $connection->query('SELECT 1')->fetchColumn() === 1
                && $connection->query('SHOW FULL TABLES')->fetchColumn() === false;
        } catch (PDOException) {
            return false;
        } finally {
            unset($connection);
            if ($previousReadTimeout !== false) {
                ini_set('mysqlnd.net_read_timeout', $previousReadTimeout);
            }
        }
    }
}
