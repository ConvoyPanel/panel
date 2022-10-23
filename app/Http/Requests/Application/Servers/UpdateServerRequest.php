<?php

namespace Convoy\Http\Requests\Application\Servers;

use Convoy\Http\Requests\Application\ApplicationFormRequest;
use Convoy\Models\Server;

class UpdateServerRequest extends ApplicationFormRequest
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

        return [
            'name' => $rules['name'],
            'node_id' => $rules['node_id'],
            'user_id' => $rules['user_id'],
            'vmid' => $rules['vmid'],
        ];
    }
}
