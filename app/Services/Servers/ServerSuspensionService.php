<?php

namespace App\Services\Servers;

use App\Enums\Server\PowerCommand;
use App\Enums\Server\SuspensionAction;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxPowerClient;
use Exception;

class ServerSuspensionService
{
    public function __construct(private ProxmoxPowerClient $powerClient) {}

    /**
     * Suspend or unsuspend a server.
     *
     * Writes `suspended_at` only. The server's lifecycle is left exactly where it was, so an
     * install that was in flight when the suspension landed is still an install when it is
     * lifted -- the old single-column scheme had to overwrite the stage and guess `ready` on
     * the way back.
     */
    public function toggle(Server $server, SuspensionAction $action = SuspensionAction::SUSPEND): void
    {
        $isSuspending = $action === SuspensionAction::SUSPEND;

        // Nothing needs to happen if we're suspending a server that is already suspended, or
        // unsuspending one that isn't.
        if ($isSuspending === $server->isSuspended()) {
            return;
        }

        $previous = $server->suspended_at;

        $server->update([
            'suspended_at' => $isSuspending ? now() : null,
        ]);

        try {
            $this->powerClient->setServer($server)->send($isSuspending ? PowerCommand::KILL : PowerCommand::START);
        } catch (Exception $exception) {
            // Restore the timestamp we replaced rather than recomputing it: on a failed
            // unsuspend that puts the original suspension time back, instead of silently
            // resetting the clock to now.
            $server->update(['suspended_at' => $previous]);

            throw $exception;
        }
    }
}
