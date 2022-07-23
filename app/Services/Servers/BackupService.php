<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class BackupService extends ProxmoxService
{
    public function getBackups()
    {
        $data = $this->nodeInstance()->storage()->zone('local')->content()->get(['content' => 'backup', 'vmid' => $this->server->vmid]);

        return $data ? $data['data'] : [];
    }
}
