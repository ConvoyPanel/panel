<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Deployments\CloudinitAddressConfigData;
use Convoy\Data\Server\Proxmox\Config\AddressConfigData;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Illuminate\Support\Arr;

/**
 * Class SnapshotService
 */
class CloudinitService
{
    public function __construct(private ProxmoxConfigRepository $configRepository)
    {
    }

    public function getSSHKeys(Server $server): string
    {
        $raw = collect($this->configRepository->setServer($server)->getConfig())->where('key', '=', 'sshkeys')->first()['value'] ?? '';

        return rawurldecode($raw);
    }

    /**
     * @param  string  $password
     * @param  array  $params
     * @return mixed
     */

    /**
     * @param  array  $params
     * @return mixed
     */
    public function updateHostname(Server $server, string $hostname)
    {
        $this->configRepository->setServer($server)->update([
            'name' => $hostname,
        ]);

        $this->configRepository->setServer($server)->update(['searchdomain' => $hostname]);
    }

    public function getNameservers(Server $server)
    {
        $nameservers = collect($this->configRepository->setServer($server)->getConfig())->where('key', '=', 'nameserver')->firstOrFail()['value'];

        return $nameservers ? explode(' ', $nameservers) : [];
    }

    public function updateNameservers(Server $server, array $nameservers)
    {
        $payload = [
            ...(count($nameservers) > 0 ? ['nameserver' => implode(' ', $nameservers)] : []),
            ...(count($nameservers) === 0 ? ['delete' => 'nameserver'] : []),
        ];

        return $this->configRepository->setServer($server)->update($payload);
    }

    public function getIpConfig(Server $server): AddressConfigData
    {
        $rawConfig = collect($this->configRepository->setServer($server)->getConfig())->where('key', '=', 'ipconfig0')->first()['value'];

        $config = [
            'ipv4' => null,
            'ipv6' => null,
        ];

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
            $payload[] = 'gw='.$ipv4->gateway;
        }

        if ($addresses?->ipv6) {
            $ipv6 = $addresses->ipv6;
            $payload[] = "ip6={$ipv6->address}/{$ipv6->cidr}";
            $payload[] = 'gw6='.$ipv6->gateway;
        }

        return $this->configRepository->setServer($server)->update([
            'ipconfig0' => Arr::join($payload, ','),
        ]);
    }
}
