<?php

namespace App\Http\Requests\Client\Servers\Backups;

use App\Enums\Server\BackupCompressionType;
use App\Enums\Server\BackupMode;
use App\Http\Requests\BaseApiRequest;
use App\Models\Backup;
use App\Models\Server;
use Illuminate\Validation\Rules\Enum;

class StoreBackupRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', $this->parameter('server', Server::class));
    }

    public function rules(): array
    {
        $rules = Backup::getRules();

        return [
            'name' => $rules['name'],
            'is_locked' => $rules['is_locked'],
            'mode' => ['required', new Enum(BackupMode::class)],
            'compression_type' => ['required', new Enum(BackupCompressionType::class)],
        ];
    }
}
