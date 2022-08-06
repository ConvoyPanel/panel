<?php

namespace App\Repositories\Proxmox\Server;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
use App\Transformers\Proxmox\CidrTransformer;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ProxmoxCloudinitRepository extends ProxmoxRepository
{
    public function getConfig()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/qemu/%s/config', $this->node->cluster, $this->server->vmid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        $data = json_decode($response->getBody(), true);
        return $data['data'] ?? $data;
    }

    public function getIpConfig()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/qemu/%s/pending', $this->node->cluster, $this->server->vmid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        $json = json_decode($response->getBody(), true);
        $data = $json['data'] ?? $json;

        $config = [
            'ipv4' => null,
            'ipv6' => null,
        ];

        $rawConfig = collect($data)->where('key', 'ipconfig0')->first();

        if ($rawConfig)
        {
            $properties = explode(',', Arr::get($rawConfig, 'value'));

            Arr::map($properties, function ($value) use (&$config) {
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
}