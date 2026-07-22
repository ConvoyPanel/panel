<?php

namespace App\Services\Proxmox\Cluster;

use App\Data\Cluster\ClusterResourceSnapshot;
use App\Data\Cluster\NodeResourceData;
use App\Data\Cluster\ServerResourceData;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function array_filter;

class ProxmoxResourceClient extends ProxmoxClient
{
    /**
     * @return Collection<int, ServerResourceData>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getResources(): Collection
    {
        $servers = array_filter($this->fetchResources(), function (array $resource) {
            return Arr::get($resource, 'type') === 'qemu';
        });

        return ServerResourceData::collect($servers, Collection::class);
    }

    /**
     * Decode the host and guest rows from one request for the scheduled poller.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getResourceSnapshot(): ClusterResourceSnapshot
    {
        $resources = $this->fetchResources();

        $nodes = array_filter(
            $resources,
            fn (array $resource) => Arr::get($resource, 'type') === 'node',
        );
        $servers = array_filter(
            $resources,
            fn (array $resource) => Arr::get($resource, 'type') === 'qemu',
        );

        return new ClusterResourceSnapshot(
            nodes: NodeResourceData::collect($nodes, Collection::class),
            servers: ServerResourceData::collect($servers, Collection::class),
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchResources(): array
    {
        $response = $this->getHttpClient()
            ->get('/api2/json/cluster/resources')
            ->json();

        return $this->getData($response);
    }
}
