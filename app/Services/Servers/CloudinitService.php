<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\ProxmoxService;
use App\Enums\Servers\Cloudinit\AuthenticationType;
use App\Enums\Servers\Cloudinit\BiosType;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class CloudinitService extends ProxmoxService
{

    public function fetchConfig()
    {
        return $this->instance()->config()->get();
    }

    /**
     * @param string $password
     * @param array $params
     * @return mixed
     */
    public function changePassword(string $password, AuthenticationType $type)
    {
        return $this->instance()->config()->post([$type->value => $password]);
    }

    /**
     * @param BiosType $type
     * @param array $params
     * @return mixed
     */
    // Generally needed for Windows VM's with over 2TB disk, still WIP since I still need to add EFI disk
    public function changeBIOS(BiosType $type)
    {
        return $this->instance()->config()->post(['bios' => $type->value]);
    }

    /**
     * @param string $hostname
     * @param array $params
     * @return mixed
     */
    public function changeHostname(string $hostname)
    {
        return $this->instance()->config()->post(['searchdomain' => $hostname]);
    }

    /**
     * @param string $dns
     * @param array $params
     * @return mixed
     */
    public function changeNameserver(string $nameserver)
    {
        return $this->instance()->config()->post(['nameserver' => $nameserver]);
    }

    public function getServerInaccessibleConfig()
    {
        return ['nameserver' => 'server inaccessible,server inaccessible', 'bios' => 'seabios', 'searchdomain' => 'server inaccessible'];
    }


}
