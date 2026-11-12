<?php

namespace App\Installation;

use RuntimeException;

final class PrivateInstallationFile
{
    public static function replace(string $path, #[\SensitiveParameter] string $contents): void
    {
        if (is_link($path) || (file_exists($path) && ! is_file($path))) {
            throw new RuntimeException('Invalid installation file target.');
        }

        $prefix = basename($path) === '.env' ? '/.env.install-' : '/.installer-';
        $temporary = dirname($path).$prefix.bin2hex(random_bytes(16));
        $handle = @fopen($temporary, 'xb');
        if ($handle === false) {
            throw new RuntimeException('Private installation file could not be created.');
        }

        try {
            try {
                $private = DIRECTORY_SEPARATOR === '\\' || @chmod($temporary, 0600);
                if (! $private || @fwrite($handle, $contents) !== strlen($contents) || ! @fflush($handle) || ! @fsync($handle)) {
                    throw new RuntimeException('Private installation file could not be written.');
                }
            } finally {
                fclose($handle);
            }

            if (! @rename($temporary, $path)) {
                throw new RuntimeException('Private installation file could not be replaced.');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
