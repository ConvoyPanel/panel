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
        // The command is part of the question: starting a server and pulling its power are
        // separate permissions on one endpoint, so the gate has to see which was asked for.
        return $this->user()->can('sendPowerCommand', [
            $this->parameter('server', Server::class),
            PowerCommand::tryFrom((string) $this->input('command')),
        ]);
    }

    public function rules(): array
    {
        return [
            // A command, not a state: `start` is an instruction to the hypervisor, and the
            // state it produces (`running`) has a different name. See PowerCommand.
            'command' => ['required', new Enum(PowerCommand::class)],
        ];
    }
}
