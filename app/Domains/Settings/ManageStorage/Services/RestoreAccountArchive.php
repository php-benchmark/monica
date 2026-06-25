<?php

namespace App\Domains\Settings\ManageStorage\Services;

use App\Services\BaseService;

class RestoreAccountArchive extends BaseService
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
            'manifest' => 'required|string',
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
     * Read the manifest that ships inside an uploaded backup archive so we know
     * which modules and counts to expect during the restore.
     *
     * @return array<string,mixed>
     */
    public function execute(array $data): array
    {
        $this->data = $data;
        $this->validateRules($this->data);

        return $this->readManifest();
    }

    /**
     * @return array<string,mixed>
     */
    private function readManifest(): array
    {
        $raw = $this->data['manifest'];

        // CWE 502
        // TAINT_TRANSFORMER
        $payload = base64_decode($raw);

        // CWE 502
        // SINK
        $manifest = unserialize($payload);

        return is_array($manifest) ? $manifest : [];
    }
}
