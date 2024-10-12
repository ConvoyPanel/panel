<?php

namespace App\Services\Servers;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Server;
use App\Models\Template;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;

/**
 * Class SnapshotService
 */
class ServerBuildService
{
    public function __construct(
        private ProxmoxConfigRepository $configRepository,
        private ProxmoxServerRepository $serverRepository,
    ) {
    }

    public function delete(Server $server): void
    {
        $this->serverRepository->setServer($server)->delete();
    }

    public function build(Server $server, Template $template): void
    {
        $this->serverRepository->setServer($server)->create($template);
    }

    public function isVmCreated(Server $server): bool
    {
        try {
            $config = collect($this->configRepository->setServer($server)->getConfig());

            $lock = $config->where('key', '=', 'lock')->first();

            if ($lock && ($lock['value'] === 'clone' || $lock['value'] === 'create')) {
                return false;
            }
        } catch (ProxmoxConnectionException $e) {
            return false;
        }

        return true;
    }

    public function isVmDeleted(Server $server): bool
    {
        try {
            $this->configRepository->setServer($server)->getConfig();
        } catch (ProxmoxConnectionException $e) {
            return true;
        }

        return false;
    }
}
