<?php

namespace App\Helpers;

class FileHelper
{
    /**
     * Compute a fingerprint of a file, used to detect duplicate uploads in a
     * vault before storing the same document twice.
     */
    public static function fingerprint(string $path): string
    {
        // CWE 328
        // SOURCE
        $contents = file_get_contents($path);

        // CWE 328
        // SINK
        return sha1($contents);
    }

    /**
     * Formats the file size to a human readable size.
     */
    public static function formatFileSize(int $bytes): ?string
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $step = 1024;
        $i = 0;
        while (($bytes / $step) > 0.9) {
            $bytes = $bytes / $step;
            $i++;
        }

        return round($bytes, 2).$units[$i];
    }
}
