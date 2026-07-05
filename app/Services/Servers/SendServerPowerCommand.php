<?php

namespace App\Services\Servers;

use App\Enums\Server\PowerCommand;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Illuminate\Http\Client\ConnectionException;

/**
 * Shared power-action pipeline for interactive (non-deployment) power commands.
 *
 * Both the client and admin server controllers delegate here so there is one
 * PowerCommand vocabulary and one path to Proxmox. The only difference between
 * the two surfaces is authorization/scoping (owner-scoped vs. root-admin),
 * which lives in the request/route layer — not here.
 */
class SendServerPowerCommand
{
    public function __construct(
        private ProxmoxPowerRepository $repository,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(Server $server, PowerCommand $command): void
    {
        $this->repository->setServer($server)->send($command);
    }
}
