<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\CloudinitAddressConfigData;
use Convoy\Data\Server\Eloquent\ServerAddressesData;
use Convoy\Data\Server\Proxmox\Config\AddressConfigData;
use Convoy\Enums\Server\Cloudinit\AuthenticationType;
use Convoy\Enums\Server\Cloudinit\BiosType;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Objects\Server\Configuration\AddressConfigObject;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxCloudinitRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;

/**
 * Class SnapshotService
 */
class CloudinitService extends ProxmoxService
{
    public function __construct(private ProxmoxCloudinitRepository $repository, private ProxmoxAllocationRepository $allocationRepository)
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

    /**
     * @param  string  $hostname
     * @param  array  $params
     * @return mixed
     */
    public function updateHostname(Server $server, string $hostname)
    {
        $this->allocationRepository->setServer($server)->update([
            'name' => $hostname,
        ]);

        return $this->repository->setServer($server)->update(['searchdomain' => $hostname]);
    }

    public function getNameservers(Server $server)
    {
        $nameservers = Arr::get($this->repository->setServer($server)->getConfig(), 'nameserver');

        return $nameservers ? explode(' ', $nameservers) : [];
    }

    public function updateNameservers(Server $server, array $nameservers)
    {
        return $this->repository->setServer($server)->update(['nameserver' => implode(' ', $nameservers)]);
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
    public function updateIpConfig(Server $server, CloudinitAddressConfigData $addresses)
    {
        $payload = [];

        if ($addresses?->ipv4) {
            $ipv4 = $addresses->ipv4;
            $payload[] = "ip={$ipv4->address}/{$ipv4->cidr}";
            $payload[] = 'gw=' . $ipv4->gateway;
        }

        if ($addresses?->ipv6) {
            $ipv6 = $addresses->ipv6;
            $payload[] = "ip6={$ipv6->address}/{$ipv6->cidr}";
            $payload[] = 'gw6=' . $ipv6->gateway;
        }

        return $this->repository->setServer($server)->update([
            'ipconfig0' => Arr::join($payload, ','),
        ]);
    }
}
