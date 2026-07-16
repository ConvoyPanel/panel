<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\Snapshot\SnapshotData;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

class ProxmoxSnapshotClient extends ProxmoxClient
{
    /**
     * @return Collection<int, SnapshotData>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getSnapshots(): Collection
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/snapshot')
            ->json();

        return SnapshotData::collect($this->getData($response), Collection::class);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function create(string $name, ?string $description = null, bool $includesRam = false): string
    {
        $payload = array_filter([
            'snapname' => $name,
            'description' => $description,
            'vmstate' => $includesRam ? 1 : null,
        ], fn ($value) => filled($value));

        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/snapshot', $payload)
            ->json();

        return $this->getData($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function restore(string $name): string
    {
        $response = $this->getHttpClientWithParams([
            'snapshot' => $name,
        ])
            ->asForm()
            ->post('/api2/json/nodes/{node}/qemu/{server}/snapshot/{snapshot}/rollback')
            ->json();

        return $this->getData($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function delete(string $name): string
    {
        $response = $this->getHttpClientWithParams([
            'snapshot' => $name,
        ])
            ->delete('/api2/json/nodes/{node}/qemu/{server}/snapshot/{snapshot}')
            ->json();

        return $this->getData($response);
    }
}
