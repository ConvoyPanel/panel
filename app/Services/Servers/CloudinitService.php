<?php

namespace Convoy\Services\Servers;

use Convoy\Enums\Servers\Cloudinit\AuthenticationType;
use Convoy\Enums\Servers\Cloudinit\BiosType;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Objects\Server\Configuration\AddressConfigObject;
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
    public function changePassword(string $password, AuthenticationType $type)
    {
        $this->repository->setServer($this->server);

        if (AuthenticationType::KEY === $type) {
            return $this->repository->update([$type->value => rawurlencode($password)]);
        } else {
            return $this->repository->update([$type->value => $password]);
        }
    }

    /**
     * @param  BiosType  $type
     * @param  array  $params
     * @return mixed
     */
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

    public function getIpConfig(): AddressConfigObject
    {
        $data = $this->repository->setServer($this->server)->getConfig();

        $config = [
            'ipv4' => null,
            'ipv6' => null,
        ];

        $rawConfig = Arr::get($data, 'ipconfig0');

        if ($rawConfig) {
            $configs = explode(',', $rawConfig);

            Arr::map($configs, function ($value) use (&$config, $data) {
                $property = explode('=', $value);

                if ($property[0] === 'ip') {
                    $cidr = explode('/', $property[1]);
                    $config['ipv4']['address'] = $cidr[0];
                    $config['ipv4']['cidr'] = $cidr[1];

                    $matches = [];
                    preg_match("/\b[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}:[[:xdigit:]]{2}\b/su", Arr::get($data, 'net0', ''), $matches);

                    $config['ipv4']['mac_address'] = $matches[0] ?? null;
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

        return AddressConfigObject::from($config);
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
