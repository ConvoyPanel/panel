<?php

namespace App\Repositories\Proxmox\Server;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
use App\Transformers\Proxmox\CidrTransformer;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

/**
 *
 */
class ProxmoxCloudinitRepository extends ProxmoxRepository
{
    /**
     * @return mixed
     * @throws ProxmoxConnectionException
     */
    public function getConfig()
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/qemu/%s/config', $this->node->cluster, $this->server->vmid));
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }

    public function update(array $params = [])
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->put(sprintf('/api2/json/nodes/%s/qemu/%s/config', $this->node->cluster, $this->server->vmid), [
                'json' => $params
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }
}