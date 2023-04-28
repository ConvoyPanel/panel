<?php

namespace Convoy\Http\Requests\Admin\Servers\Settings;

use Convoy\Http\Requests\FormRequest;
use Convoy\Models\Server;
use Convoy\Rules\Hostname;

class UpdateGeneralInfoRequest extends FormRequest
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
        $rules = Server::getRulesForUpdate($this->parameter('server', Server::class));

        return $this->requiredToOptional([
            'name' => $rules['name'],
            'hostname' => [...$rules['hostname'], ...[new Hostname]],
            'node_id' => $rules['node_id'],
            'user_id' => $rules['user_id'],
            'vmid' => $rules['vmid'],
            'status' => $rules['status'],
        ]);
    }
}
