<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Servers\Status;
use Convoy\Enums\Servers\SuspensionAction;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Convoy\Services\ProxmoxService;
use Webmozart\Assert\Assert;

class SuspensionService extends ProxmoxService
{
    public function __construct(private ProxmoxPowerRepository $powerRepository)
    {
    }

    public function toggle(SuspensionAction $action = SuspensionAction::SUSPEND)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $isSuspending = $action === SuspensionAction::SUSPEND;

        // Nothing needs to happen if we're suspending the server and it is already
        // suspended in the database. Additionally, nothing needs to happen if the server
        // is not suspended and we try to un-suspend the instance.
        if ($isSuspending === $this->server->isSuspended()) {
            return;
        }

        $this->server->update([
            'status' => $isSuspending ? Status::SUSPENDED : null,
        ]);

        try {
            $this->powerRepository->setServer($this->server)->send($isSuspending ? 'suspend' : 'resume');
        } catch (\Exception $exception) {
            $this->server->update([
                'status' => $isSuspending ? null : Status::SUSPENDED,
            ]);

            throw $exception;
        }
    }
}