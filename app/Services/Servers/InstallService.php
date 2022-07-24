<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class InstallService extends ProxmoxService
{
    private PowerService $powerService;

    public function __construct()
    {
        $this->powerService = new PowerService();
    }

    public function install(int $newid, string $target)
    {
        return $this->instance()->clone()->post(['newid' => $newid, 'target' => $target]);
    }

    public function delete(bool $destroyUnreferencedDisks = true, bool $purgeJobConfigurations = true)
    {
        $this->powerService->setServer($this->server)->kill();

        return $this->instance()->delete(['destroy-unreferenced-disks' => $destroyUnreferencedDisks, 'purge' => $purgeJobConfigurations]);
    }

    public function reinstall(Server $template)
    {
        $originalServer = clone $this->server;

        $this->powerService->setServer($originalServer)->kill();

        $this->delete();

        return $this->setServer($template)->install($originalServer->vmid, $this->node->cluster);
    }
}
