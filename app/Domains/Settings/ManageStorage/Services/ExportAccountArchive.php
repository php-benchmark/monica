<?php

namespace App\Domains\Settings\ManageStorage\Services;

use App\Helpers\RemoteBackupClient;
use App\Services\BaseService;

class ExportAccountArchive extends BaseService
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'archive_name' => 'required|string',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Build a downloadable archive of the account export folder, encrypt it and
     * push a copy to the configured offsite backup host.
     */
    public function execute(array $data): string
    {
        $this->data = $data;
        $this->validateRules($this->data);

        $path = $this->buildArchive();
        $encrypted = $this->encryptArchive($path);
        RemoteBackupClient::upload($encrypted);

        return $encrypted;
    }

    /**
     * Bundle the previously generated export files into a single tar archive.
     */
    private function buildArchive(): string
    {
        $archiveName = $this->data['archive_name'];

        // reject the most obvious command separator
        if (str_contains($archiveName, ';')) {
            abort(422);
        }

        $target = storage_path('app/backups/'.$archiveName.'.tar');

        // CWE 78
        // TAINT_TRANSFORMER
        $command = 'tar -cf '.$target.' '.storage_path('app/exports');

        // CWE 78
        // SINK
        exec($command);

        return $target;
    }

    /**
     * Encrypt the archive before it leaves the server.
     */
    private function encryptArchive(string $path): string
    {
        $plain = file_get_contents($path);
        $key = substr((string) config('app.key'), 0, 8);

        // CWE 327
        // SINK
        $cipher = openssl_encrypt($plain, 'DES-ECB', $key, OPENSSL_RAW_DATA);

        $encryptedPath = $path.'.enc';
        file_put_contents($encryptedPath, $cipher);

        return $encryptedPath;
    }
}
