<?php

namespace App\Services\Servers;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use App\Services\ProxmoxService;
use App\Enums\Servers\Cloudinit\AuthenticationType;
use App\Enums\Servers\Cloudinit\BiosType;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class CloudinitService extends ProxmoxService
{
    protected ProxmoxCloudinitRepository $cloudinitRepository;

    public function __construct()
    {
        $this->cloudinitRepository = new ProxmoxCloudinitRepository();
    }

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

    public function updateIpConfig(string|array $config)
    {
        $this->cloudinitRepository->setServer($this->server);

        if (gettype($config) === 'string')
        {
            return $this->cloudinitRepository->update([
                'ipconfig0' => $config,
            ]);
        }

        if (gettype($config) === 'array')
        {
            $payload = [];

            if (isset($config['ipv4']))
            {
                $ipv4 = $config['ipv4'];
                $payload[] = "ip={$ipv4['address']}/{$ipv4['cidr']}";
                $payload[] = 'gw=' . $ipv4['gateway'];
            }

            if (isset($config['ipv6']))
            {
                $ipv6 = $config['ipv6'];
                $payload[] = "ip6={$ipv6['address']}/{$ipv6['cidr']}";
                $payload[] = 'gw6=' . $ipv6['gateway'];
            }

            return $this->cloudinitRepository->update([
                'ipconfig0' => Arr::join($payload, ','),
            ]);
        }
    }

    public function getServerInaccessibleConfig()
    {
        return ['nameserver' => 'server inaccessible,server inaccessible', 'bios' => 'seabios', 'searchdomain' => 'server inaccessible'];
    }


}
