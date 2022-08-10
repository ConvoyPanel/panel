<?php

namespace App\Http\Requests\Application\Servers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpecificationsRequest extends FormRequest
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
            'cores' => 'numeric',
            'memory' => 'numeric',
            'disks' => 'array',
            'disks.*.disk' => 'string|required',
            'disks.*.size' => 'string|required',
            'ipconfig' => 'string',
            'lockIps' => 'array',
            'lockIps.*' => 'ip|required',
        ];
    }
}
