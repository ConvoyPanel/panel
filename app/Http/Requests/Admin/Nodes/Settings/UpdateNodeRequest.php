<?php

namespace Convoy\Http\Requests\Admin\Nodes\Settings;

use Convoy\Enums\Proxmox\AuthenticationType;
use Convoy\Models\Node;
use Convoy\Rules\Network\Hostname;
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
        $rules = Node::getRulesForUpdate($this->parameter('node', Node::class));

        return [
            'name' => $rules['name'],
            'cluster' => $rules['cluster'],
            'hostname' => $rules['hostname'],
            'token_id' => 'nullable|string',
            'secret' => 'nullable|string',
            'port' => $rules['port'],
            'network' => $rules['network'],
            'storage' => $rules['storage'],
        ];
    }
}
