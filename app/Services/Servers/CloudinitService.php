<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Proxmox\Config\AddressConfigData;
use Convoy\Enums\Server\Cloudinit\AuthenticationType;
use Convoy\Enums\Server\Cloudinit\BiosType;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Objects\Server\Configuration\AddressConfigObject;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;

/**
 * Class SnapshotService
 */
class CloudinitService extends ProxmoxService
{
    public function __construct(protected ProxmoxCloudinitRepository $repository)
    {
    }

    /**
     * @param  string  $password
     * @param  array  $params
     * @return mixed
     */
    public function changePassword(?string $password, AuthenticationType $type)
    {
        $this->repository->setServer($this->server);

        if (AuthenticationType::KEY === $type) {
            if (!empty($password)) {
                return $this->repository->update([$type->value => rawurlencode($password)]);
            } else {
                $this->repository->update(['delete' => $type->value]);
            }
        } else {
            return $this->repository->update([$type->value => $password]);
        }
    }

    // Generally needed for Windows VM's with over 2TB disk, still WIP since I still need to add EFI disk
    /**
     * @param  BiosType  $type
     * @return mixed
     *
     * @throws ProxmoxConnectionException
     */
    public function changeBIOS(BiosType $type)
    {
        return $this->repository->setServer($this->server)->update(['bios' => $type->value]);
    }

    /**
     * @param  string  $hostname
     * @param  array  $params
     * @return mixed
     */
    public function changeHostname(string $hostname)
    {
        return $this->repository->setServer($this->server)->update(['searchdomain' => $hostname]);
    }

    /**
     * @param  string  $dns
     * @param  array  $params
     * @return mixed
     */
    public function changeNameserver(string $nameserver)
    {
        return $this->repository->setServer($this->server)->update(['nameserver' => $nameserver]);
    }

    public function getIpConfig(Server $server): AddressConfigData
    {
        $data = $this->repository->setServer($server)->getConfig();

        $config = [
            'ipv4' => null,
            'ipv6' => null,
        ];

        $rawConfig = Arr::get($data, 'ipconfig0');

        if ($rawConfig) {
            $configs = explode(',', $rawConfig);

            Arr::map($configs, function ($value) use (&$config) {
                $property = explode('=', $value);

                if ($property[0] === 'ip') {
                    $cidr = explode('/', $property[1]);
                    $config['ipv4']['address'] = $cidr[0];
                    $config['ipv4']['cidr'] = $cidr[1];
                }
                if ($property[0] === 'ip6') {
                    $cidr = explode('/', $property[1]);
                    $config['ipv6']['address'] = $cidr[0];
                    $config['ipv6']['cidr'] = $cidr[1];
                }
                if ($property[0] === 'gw') {
                    $config['ipv4']['gateway'] = $property[1];
                }
                if ($property[0] === 'gw6') {
                    $config['ipv6']['gateway'] = $property[1];
                }
            });
        }

        return AddressConfigData::from($config);
    }

    /**
     * @param  string|array  $config
     * @return mixed|void
     *
     * @throws ProxmoxConnectionException
     */
    public function updateIpConfig(string|array $config)
    {
        $this->repository->setServer($this->server);

        if (gettype($config) === 'string') {
            return $this->repository->update([
                'ipconfig0' => $config,
            ]);
        }

        if (gettype($config) === 'array') {
            $payload = [];

            if (isset($config['ipv4'])) {
                $ipv4 = $config['ipv4'];
                $payload[] = "ip={$ipv4['address']}/{$ipv4['cidr']}";
                $payload[] = 'gw='.$ipv4['gateway'];
            }

            if (isset($config['ipv6'])) {
                $ipv6 = $config['ipv6'];
                $payload[] = "ip6={$ipv6['address']}/{$ipv6['cidr']}";
                $payload[] = 'gw6='.$ipv6['gateway'];
            }

            return $this->repository->update([
                'ipconfig0' => Arr::join($payload, ','),
            ]);
        }
    }
}
