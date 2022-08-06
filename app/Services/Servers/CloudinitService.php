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
    /**
     * @var ProxmoxCloudinitRepository
     */
    private ProxmoxCloudinitRepository $cloudinitRepository;

    /**
     *
     */
    public function __construct()
    {
        $this->cloudinitRepository = new ProxmoxCloudinitRepository();
    }

    /**
     * @param string $password
     * @param array $params
     * @return mixed
     */
    public function changePassword(string $password, AuthenticationType $type)
    {
        $this->cloudinitRepository->setServer($this->server);

        if (AuthenticationType::KEY === $type)
        {
            return $this->cloudinitRepository->update([$type->value => rawurlencode($password)]);
        } else {
            return $this->cloudinitRepository->update([$type->value => $password]);
        }
    }

    /**
     * @param BiosType $type
     * @param array $params
     * @return mixed
     */
    // Generally needed for Windows VM's with over 2TB disk, still WIP since I still need to add EFI disk
    /**
     * @param BiosType $type
     * @return mixed
     * @throws ProxmoxConnectionException
     */
    public function changeBIOS(BiosType $type)
    {
        return $this->cloudinitRepository->setServer($this->server)->update(['bios' => $type->value]);
    }

    /**
     * @param string $hostname
     * @param array $params
     * @return mixed
     */
    public function changeHostname(string $hostname)
    {
        return $this->cloudinitRepository->setServer($this->server)->update(['searchdomain' => $hostname]);
    }

    /**
     * @param string $dns
     * @param array $params
     * @return mixed
     */
    public function changeNameserver(string $nameserver)
    {
        return $this->cloudinitRepository->setServer($this->server)->update(['nameserver' => $nameserver]);
    }

    public function getIpConfig(): array
    {
        $data = $this->cloudinitRepository->setServer($this->server)->getConfig();

        $config = [
            'ipv4' => null,
            'ipv6' => null,
        ];

        $rawConfig = collect($data)->where('key', 'ipconfig0')->first();

        if ($rawConfig)
        {
            $configs = explode(',', Arr::get($rawConfig, 'value'));

            Arr::map($configs, function ($value) use (&$config) {
                $property = explode('=', $value);

                if ($property[0] === 'ip')
                {
                    $cidr = explode('/', $property[1]);
                    $config['ipv4']['address'] = $cidr[0];
                    $config['ipv4']['cidr'] = $cidr[1];
                }
                if ($property[0] === 'ip6')
                {
                    $cidr = explode('/', $property[1]);
                    $config['ipv6']['address'] = $cidr[0];
                    $config['ipv6']['cidr'] = $cidr[1];
                }
                if ($property[0] === 'gw')
                    $config['ipv4']['gateway'] = $property[1];
                if ($property[0] === 'gw6')
                    $config['ipv6']['gateway'] = $property[1];
            });
        }

        return $config;
    }

    /**
     * @param string|array $config
     * @return mixed|void
     * @throws ProxmoxConnectionException
     */
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
}
