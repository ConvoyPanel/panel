<?php

namespace App\Repositories\Proxmox\Cluster;

use App\Data\Cluster\ServerResourceData;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function array_filter;

class ProxmoxResourceRepository extends ProxmoxRepository
{
    /**
     * @return Collection<int, ServerResourceData>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getResources(): Collection
    {
        $response = $this->getHttpClient()
            ->get('/api2/json/cluster/resources')
            ->json();

        $servers = array_filter($this->getData($response), function (array $resource) {
            return Arr::get($resource, 'type') === 'qemu';
        });

        return ServerResourceData::collect($servers, Collection::class);
    }
}
