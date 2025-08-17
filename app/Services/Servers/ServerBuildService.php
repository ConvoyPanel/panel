<?php

namespace App\Services\Servers;

use App\Enums\Server\LockStatus;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Repositories\Proxmox\Cluster\ProxmoxResourceRepository;
use App\Models\Server;
use App\Models\Template;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;

class ServerBuildService
{
    public function __construct(
        private ProxmoxServerRepository $serverRepository,
        private ProxmoxResourceRepository $resourceRepository,
    ) {
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function delete(Server $server): void
    {
        $this->serverRepository->setServer($server)->delete();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function build(Server $server, Template $template): void
    {
        $this->serverRepository->setServer($server)->create($template);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isVmCreated(Server $server): bool
    {
        $servers = $this->resourceRepository->setServer($server)->getResources();

        $vm = $servers->where('vmid', $server->vmid)
            ->where('lockStatus', '!=', LockStatus::CLONE)
            ->first();

        if ($vm) {
            return true;
        }

        return false;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function isVmDeleted(Server $server): bool
    {
        $servers = $this->resourceRepository->setServer($server)->getResources();

        $vm = $servers->where('vmid', $server->vmid)->first();

        if ($vm) {
            return false;
        }

        return true;
    }
}
