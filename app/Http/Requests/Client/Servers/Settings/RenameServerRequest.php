<?php

namespace Convoy\Http\Requests\Client\Servers\Settings;

use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Server;
use Convoy\Rules\Hostname;

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
