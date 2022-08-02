<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class AgentService extends ProxmoxService
{
    /**
     * @param string $command
     * @return mixed
     */
    public function executeCommand(string $command)
    {
        return $this->instance()->agent()->exec($command);
    }

    /**
     * @return mixed
     */
    public function getOSInfo()
    {
        return $this->instance()->agent()->getOsinfo()->get();
    }
}
