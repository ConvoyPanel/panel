<?php

namespace App\Http\Requests\Admin\Servers\Settings;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Rules\Hostname;

class UpdateGeneralInfoRequest extends BaseApiRequest
{
    public function rules(): array
    {
        $rules = Server::getRulesForUpdate($this->parameter('server', Server::class));

        return $this->requiredToOptional([
            'name' => $rules['name'],
            'hostname' => [...$rules['hostname'], ...[new Hostname()]],
            'user_id' => $rules['user_id'],
            'vmid' => $rules['vmid'],
            'status' => $rules['status'],
        ]);
    }
}
