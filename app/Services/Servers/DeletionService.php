<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\ProxmoxService;

/**
 * Class ServerDeletionService
 * @package App\Services\Servers
 */
class DeletionService extends ProxmoxService
{
    /**
     * @param string $cluster
     * @param string $vmid
     * @param array $params
     * @return mixed
     */
    public function destroy(array $params)
    {
        return $this->proxmox()->delete($params); // WIP
    }
}