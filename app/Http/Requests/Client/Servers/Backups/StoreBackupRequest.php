<?php

namespace Convoy\Http\Requests\Client\Servers\Backups;

use Convoy\Enums\Server\BackupCompressionType;
use Convoy\Enums\Server\BackupMode;
use Convoy\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreBackupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
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
