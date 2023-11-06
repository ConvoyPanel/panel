<?php

namespace Convoy\Http\Requests\Client\Servers\Backups;

use Convoy\Models\Backup;
use Convoy\Enums\Server\BackupMode;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Convoy\Enums\Server\BackupCompressionType;

class StoreBackupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
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
