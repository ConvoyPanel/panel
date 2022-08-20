<?php

namespace App\Http\Requests\Admin\Nodes\Settings;

use App\Enums\Proxmox\AuthenticationType;
use App\Rules\Network\Hostname;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateNodeRequest extends FormRequest
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
            'name' => 'string|required',
            'cluster' => 'string|required',
            'hostname' => [new Hostname, 'required'],
            'token_id' => 'string|nullable',
            'secret' => 'string|nullable',
            'port' => 'integer|required',
        ];
    }
}
