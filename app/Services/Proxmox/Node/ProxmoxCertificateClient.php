<?php

namespace App\Services\Proxmox\Node;

use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;

class ProxmoxCertificateClient extends ProxmoxClient
{
    /**
     * The fingerprint of the host's `pve-root-ca.pem` -- the one stable,
     * collision-free identity a PVE cluster has.
     *
     * The CA is generated once at install time, lives in pmxcfs, and is
     * replicated verbatim to every member, so all members of a cluster answer
     * with the same fingerprint and no two clusters can answer with one.
     * Unlike the cluster *name* it cannot be typed wrong (nobody types it) and
     * does not change when members join or leave.
     *
     * Null when the listing carries no root CA row, which no supported PVE
     * version does in practice -- treated by callers as "could not identify",
     * never as an identity.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getClusterCaFingerprint(): ?string
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/certificates/info')
            ->json();

        foreach ($this->getData($response) as $row) {
            if (str_ends_with((string) Arr::get($row, 'filename', ''), 'pve-root-ca.pem')) {
                return Arr::get($row, 'fingerprint');
            }
        }

        return null;
    }
}
