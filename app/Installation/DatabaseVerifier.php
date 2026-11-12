<?php

namespace App\Installation;

use PDOException;
use RuntimeException;

/** @phpstan-import-type Settings from InstallerDatabase */
class DatabaseVerifier
{
    public function __construct(private InstallerDatabase $database) {}

    /** @param Settings $settings */
    public function verify(#[\SensitiveParameter] array $settings): bool
    {
        $name = 'installer_verification';
        $readTimeout = ini_set('mysqlnd.net_read_timeout', '3');

        try {
            if (($settings['engine'] ?? 'mysql') === 'sqlite' && ! $this->database->sqliteFileAvailable(true)) {
                return false;
            }

            $connection = $this->database->connect($name, $settings, verification: true);

            return (int) $connection->scalar('SELECT 1', [], false) === 1
                && $this->database->matchesTarget($connection, $settings['database'])
                && $this->database->empty($connection);
        } catch (PDOException|RuntimeException) {
            return false;
        } finally {
            $this->database->close($name);
            if ($readTimeout !== false) {
                ini_set('mysqlnd.net_read_timeout', $readTimeout);
            }
        }
    }
}
