<?php

namespace Convoy\Services\Servers;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;

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
