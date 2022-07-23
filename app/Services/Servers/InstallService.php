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
    public function install(int $newid, string $target)
    {
        return $this->instance()->clone()->post(['newid' => $newid, 'target' => $target]);
    }

    public function delete(bool $destroyUnreferencedDisks = true, bool $purgeJobConfigurations = true)
    {
        return $this->instance()->delete(['destroy-unreferenced-disks' => $destroyUnreferencedDisks, 'purge' => $purgeJobConfigurations]);
    }

    public function reinstall(Server $target, Server $clone)
    {
        $this->setServer($target)->delete();

        return $this->setServer($clone)->install($target->vmid, $this->node->cluster);
    }
}
