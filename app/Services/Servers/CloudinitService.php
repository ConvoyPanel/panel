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

    // dumps YAML formatted config (I know, it's terrible)
    public function dumpConfig(string $type = 'network')
    {
        return $this->removeDataProperty($this->instance()->cloudinit()->dump()->get(['type' => $type]));
    }

    /**
     * @param string $password
     * @param array $params
     * @return mixed
     */
    public function changePassword(string $password, AuthenticationType $type)
    {
        if (AuthenticationType::KEY === $type)
        {
            return $this->instance()->config()->post([$type->value => rawurlencode($password)]);
        } else {
            return $this->instance()->config()->post([$type->value => $password]);
        }

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

    public function getIpConfig() : ?array
    {
        $resourceService = new ResourceService();
        $configs = $resourceService->setServer($this->server)->getConfig();

        $index = array_search('ipconfig0', array_column($configs, 'key'));

        if ($index !== false)
        {
            return $configs[$index];
        }

        return null;
    }

    public function getServerInaccessibleConfig()
    {
        return ['nameserver' => 'server inaccessible,server inaccessible', 'bios' => 'seabios', 'searchdomain' => 'server inaccessible'];
    }


}
