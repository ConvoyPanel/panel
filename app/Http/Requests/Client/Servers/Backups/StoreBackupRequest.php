<?php

namespace Convoy\Http\Requests\Client\Servers\Backups;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Backup;
use Convoy\Enums\Server\BackupMode;
use Convoy\Models\Server;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Convoy\Enums\Server\BackupCompressionType;

class StoreBackupRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Backup::class, $this->parameter('server', Server::class)]);
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
