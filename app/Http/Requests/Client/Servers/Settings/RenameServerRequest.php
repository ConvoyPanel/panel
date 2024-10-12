<?php

namespace App\Http\Requests\Client\Servers\Settings;

use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use App\Rules\Hostname;

class RenameServerRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('rename', $this->parameter('server', Server::class));
    }

    public function rules(): array
    {
        return [
            'name' => Server::getRules()['name'],
            'hostname' => [...Server::getRules()['hostname'], ...[new Hostname()]],
        ];
    }
}
