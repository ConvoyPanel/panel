<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\Config\ServerConfigData;
use App\Exceptions\Http\Server\ConfigModifiedException;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;

class ProxmoxConfigClient extends ProxmoxClient
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getConfig(): ServerConfigData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/config')
            ->json();

        return ServerConfigData::fromRaw($this->getData($response));
    }

    /**
     * The raw PVE config map (unmodeled keys included, e.g. `unused0`). Used
     * when removing a disk: `delete=scsiN` only *detaches* (the volume becomes
     * `unusedN`), so we diff the raw `unused*` keys to find and destroy it.
     *
     * @return array<string, mixed>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getRawConfig(): array
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/config')
            ->json();

        return $this->getData($response);
    }

    /**
     * Update the VM config. Pass the digest captured from getConfig() to make
     * PVE reject the write if the config changed since it was read (optimistic
     * concurrency); a mismatch surfaces as a RequestException.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function update(array $payload = [], ?string $digest = null)
    {
        if ($digest !== null) {
            $payload['digest'] = $digest;
        }

        try {
            $response = $this->getHttpClientWithParams()
                ->post('/api2/json/nodes/{node}/qemu/{server}/config', $payload)
                ->json();
        } catch (RequestException $e) {
            if ($digest !== null && $this->isConfigModifiedError($e)) {
                throw new ConfigModifiedException;
            }

            throw $e;
        }

        return $this->getData($response);
    }

    /**
     * Whether the failure is Proxmox rejecting the write due to a digest
     * mismatch ("detected modified configuration - file changed by other user").
     */
    private function isConfigModifiedError(RequestException $e): bool
    {
        return Str::contains(
            Str::lower($e->getMessage()),
            ['changed by other user', 'modified configuration'],
        );
    }
}
