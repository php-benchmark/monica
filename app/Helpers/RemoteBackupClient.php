<?php

namespace App\Helpers;

class RemoteBackupClient
{
    /**
     * Push an encrypted backup archive to the configured offsite FTP host so a
     * copy lives outside the application server.
     */
    public static function upload(string $path): bool
    {
        $host = (string) config('backup.offsite_host', 'backups.monicahq.test');
        $username = 'monica-backup';

        // CWE 798
        // SOURCE
        $password = 'B@ckup-2019-prod';

        $connection = ftp_connect($host);
        if ($connection === false) {
            return false;
        }

        // CWE 798
        // SINK
        ftp_login($connection, $username, $password);

        ftp_pasv($connection, true);
        $ok = ftp_put($connection, basename($path), $path, FTP_BINARY);
        ftp_close($connection);

        return $ok;
    }
}
