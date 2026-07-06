<?php

namespace App\Services\Servers;

use App\Enums\Server\PowerCommand;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Exceptions\Http\Server\PowerActionInProgressException;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use App\Services\Servers\Power\ServerPowerLockService;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

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
        private ServerPowerLockService $lock,
    ) {}

    /**
     * @throws PowerActionInProgressException if another power action is in flight
     * @throws RequestException
     * @throws ConnectionException
     */
    public function handle(Server $server, PowerCommand $command): void
    {
        // Claim the per-server lock before touching Proxmox so two concurrent
        // requests can't enqueue conflicting tasks. Throws 409 if held.
        $this->lock->acquire($server, $command);

        try {
            $this->repository->setServer($server)->send($command);
        } catch (Throwable $e) {
            // The command never landed — free the lock so the user can retry
            // immediately rather than waiting out the TTL.
            $this->lock->release($server);

            throw $e;
        }
    }
}
