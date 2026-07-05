<?php

namespace App\Http\Requests\Servers;

use App\Enums\Server\PowerCommand;
use App\Http\Requests\BaseApiRequest;
use App\Models\Server;
use Illuminate\Validation\Rules\Enum;

/**
 * Shared by the client (owner-scoped) and admin (root-admin) power endpoints.
 * The policy check covers both: ServerPolicy::before() authorizes a root admin
 * or the server's owner, so one request serves both surfaces.
 */
class SendPowerCommandRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sendPowerCommand', $this->parameter('server', Server::class));
    }

    public function rules(): array
    {
        return [
            'state' => ['required', new Enum(PowerCommand::class)],
        ];
    }
}
