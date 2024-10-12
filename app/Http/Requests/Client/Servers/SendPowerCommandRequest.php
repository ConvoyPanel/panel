<?php

namespace App\Http\Requests\Client\Servers;

use App\Enums\Server\PowerAction;
use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
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
