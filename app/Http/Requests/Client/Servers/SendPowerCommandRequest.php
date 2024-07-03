<?php

namespace Convoy\Http\Requests\Client\Servers;

use Convoy\Enums\Server\PowerAction;
use Convoy\Http\Requests\BaseApiRequest;
use Convoy\Models\Server;
use Illuminate\Validation\Rules\Enum;

class SendPowerCommandRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sendPowerCommand', $this->parameter('server', Server::class));
    }

    public function rules(): array
    {
        return [
            'state' => ['required', new Enum(PowerAction::class)],
        ];
    }
}
