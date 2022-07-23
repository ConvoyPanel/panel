<?php

namespace App\Http\Requests\Client\Servers\Backups;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'mode' => 'required|in:snapshot,suspend,stop',
            'compression' => 'required|in:none,lzo,gzip,zstd',
        ];
    }
}
