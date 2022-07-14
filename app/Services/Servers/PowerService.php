<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\ProxmoxService;

/**
 * Class PowerService
 * @package App\Services\Servers
 */
class PowerService extends ProxmoxService
{
    /**
     * @param array $params
     * @return mixed
     */
    public function start()
    {
        return $this->instance()->status()->start()->post();
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function shutdown()
    {
        return $this->instance()->status()->shutdown()->post();
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function kill()
    {
        return $this->instance()->status()->stop()->post();
    }

    /**
     * @param string $cluster
     * @param string $vmid
     * @param array $params
     * @return mixed
     */
    public function reboot()
    {
        return $this->instance()->status()->reboot()->post();
    }
}
