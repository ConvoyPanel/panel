<?php

namespace App\Services\Servers;

use App\Enums\Server\PowerAction;
use App\Enums\Server\ServerStatus;
use App\Enums\Server\SuspensionAction;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Exception;

class ServerSuspensionService
{
    public function __construct(private ProxmoxPowerRepository $powerRepository)
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
            $this->powerRepository->setServer($server)->send($isSuspending ? PowerAction::KILL : PowerAction::START);
        } catch (Exception $exception) {
            $server->update([
                'status' => $isSuspending ? null : ServerStatus::SUSPENDED->value,
            ]);

            throw $exception;
        }
    }
}
