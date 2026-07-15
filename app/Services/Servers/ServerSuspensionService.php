<?php

namespace App\Services\Servers;

use App\Enums\Server\PowerCommand;
use App\Enums\Server\ServerStatus;
use App\Enums\Server\SuspensionAction;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxPowerClient;
use Exception;

class ServerSuspensionService
{
    public function __construct(private ProxmoxPowerClient $powerClient)
    {
    }

    public function toggle(Server $server, SuspensionAction $action = SuspensionAction::SUSPEND)
    {
        $isSuspending = $action === SuspensionAction::SUSPEND;

        // Nothing needs to happen if we're suspending the server and it is already
        // suspended in the database. Additionally, nothing needs to happen if the server
        // is not suspended and we try to un-suspend the instance.
        if ($isSuspending === $server->isSuspended()) {
            return;
        }

        $server->update([
            'status' => $isSuspending ? ServerStatus::SUSPENDED->value : null,
        ]);

        try {
            $this->powerClient->setServer($server)->send($isSuspending ? PowerCommand::KILL : PowerCommand::START);
        } catch (Exception $exception) {
            $server->update([
                'status' => $isSuspending ? null : ServerStatus::SUSPENDED->value,
            ]);

            throw $exception;
        }
    }
}
