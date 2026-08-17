<?php

namespace App\Services\Proxmox\Cluster;

use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;

class ProxmoxClusterStatusClient extends ProxmoxClient
{
    /**
     * The name of the PVE cluster this host belongs to, or null if it is standalone.
     *
     * `/cluster/status` returns one row per member plus, on a real cluster, a
     * single `type=cluster` row carrying the cluster's name. A standalone host
     * answers with only its own `type=node` row, so the absence of that row is
     * how "not clustered" is expressed -- not an error.
     *
     * This is what lets Convoy tell two hosts that genuinely share a storage
     * pool from two hosts that merely have a `local-lvm` each: PVE's
     * `storage.cfg` is cluster-wide, so a storage id is unique within a cluster
     * and means nothing across one.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getClusterName(): ?string
    {
        $response = $this->getHttpClient()
            ->get('/api2/json/cluster/status')
            ->json();

        foreach ($this->getData($response) as $row) {
            if (Arr::get($row, 'type') === 'cluster') {
                return Arr::get($row, 'name');
            }
        }

        return null;
    }
}
