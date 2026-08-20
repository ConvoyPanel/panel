<?php

namespace App\Services\Proxmox\Cluster;

use App\Data\Cluster\ClusterStatusData;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\Node\ProxmoxCertificateClient;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;

class ProxmoxClusterStatusClient extends ProxmoxClient
{
    /**
     * The cluster this host claims to be in, and who it says its peers are.
     *
     * `/cluster/status` returns one `type=node` row per member plus, on a real
     * cluster, a single `type=cluster` row carrying the cluster's name. A
     * standalone host answers with only its own node row, so the absence of
     * the cluster row is how "not clustered" is expressed -- not an error.
     *
     * The name is a label, never an identity -- two unrelated clusters both
     * named `proxmox` are commonplace. Identity comes from the cluster CA's
     * fingerprint ({@see ProxmoxCertificateClient}),
     * and the member names feed the disjoint-set tripwire that catches the one
     * way even a certificate can mislead.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getStatus(): ClusterStatusData
    {
        $response = $this->getHttpClient()
            ->get('/api2/json/cluster/status')
            ->json();

        $clusterName = null;
        $memberNames = [];

        foreach ($this->getData($response) as $row) {
            match (Arr::get($row, 'type')) {
                'cluster' => $clusterName = Arr::get($row, 'name'),
                'node' => $memberNames[] = (string) Arr::get($row, 'name'),
                default => null,
            };
        }

        return new ClusterStatusData($clusterName, $memberNames);
    }
}
