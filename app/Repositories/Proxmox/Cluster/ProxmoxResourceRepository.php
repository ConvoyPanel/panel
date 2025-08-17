<?php

namespace App\Repositories\Proxmox\Cluster;

use Illuminate\Support\Collection;
use App\Data\Cluster\ServerResourceData;
use App\Repositories\Proxmox\ProxmoxRepository;
use Spatie\LaravelData\DataCollection;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\Repository\Proxmox\RequestException;
use function array_filter;

class ProxmoxResourceRepository extends ProxmoxRepository
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     * @return Collection<int, ServerResourceData>
     */
    public function getResources(): Collection
    {
        $response = $this->getHttpClient()
            ->get('/api2/json/cluster/resources')
            ->json();

        $servers = array_filter($this->getData($response), function (array $resource) {
            return array_get($resource, 'type') === 'qemu';
        });

        return ServerResourceData::collect($servers, Collection::class);
    }
}