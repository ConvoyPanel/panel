<?php

namespace Convoy\Http\Requests\Admin\Servers\Settings;

use Convoy\Models\Server;
use Convoy\Rules\Hostname;
use Convoy\Http\Requests\FormRequest;

class UpdateGeneralInfoRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = Server::getRulesForUpdate($this->parameter('server', Server::class));

        return $this->requiredToOptional([
            'name' => $rules['name'],
            'hostname' => [...$rules['hostname'], ...[new Hostname]],
            'user_id' => $rules['user_id'],
            'vmid' => $rules['vmid'],
            'status' => $rules['status'],
        ]);
    }
}
