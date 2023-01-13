<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Server\Power;
use Convoy\Enums\Server\Status;
use Convoy\Enums\Server\SuspensionAction;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Convoy\Services\ProxmoxService;
use Webmozart\Assert\Assert;

class SuspensionService extends ProxmoxService
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
            'status' => $isSuspending ? Status::SUSPENDED->value : null,
        ]);

        try {
            $this->powerRepository->setServer($server)->send(Power::KILL);
        } catch (\Exception $exception) {
            $server->update([
                'status' => $isSuspending ? null : Status::SUSPENDED->value,
            ]);

            throw $exception;
        }
    }
}