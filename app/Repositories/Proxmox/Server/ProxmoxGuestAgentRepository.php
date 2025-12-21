<?php

namespace App\Repositories\Proxmox\Server;

use Illuminate\Support\Collection;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentExecStatusData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentFsInfoData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentInfoData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentNetworkInterfaceData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentOsInfoData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentUserData;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;
use Spatie\LaravelData\DataCollection;

class ProxmoxGuestAgentRepository extends ProxmoxRepository
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function info(): GuestAgentInfoData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/agent/info')
            ->json();

        return GuestAgentInfoData::fromRaw($this->getData($response));
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getOsInfo(): GuestAgentOsInfoData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-osinfo')
            ->json();

        return GuestAgentOsInfoData::fromRaw($this->getData($response));
    }

    /**
     * @return Collection<int, GuestAgentNetworkInterfaceData>
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getNetworkInterfaces(): Collection
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/agent/network-get-interfaces')
            ->json();

        // The result is usually in 'result' key
        $data = $this->getData($response)['result'] ?? [];

        return GuestAgentNetworkInterfaceData::collect($data, Collection::class);
    }

    /**
     * @return Collection<int, GuestAgentFsInfoData>
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getFsInfo(): Collection
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-fsinfo')
            ->json();

        $data = $this->getData($response)['result'] ?? [];

        return GuestAgentFsInfoData::collect($data, Collection::class);
    }

    /**
     * @return Collection<int, GuestAgentUserData>
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getUsers(): Collection
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-users')
            ->json();

        $data = $this->getData($response)['result'] ?? [];

        return GuestAgentUserData::collect($data, Collection::class);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function exec(array $command, ?string $input = null): int
    {
        $payload = ['command' => $command];
        if ($input !== null) {
            $payload['input-data'] = $input;
        }

        $response = $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/agent/exec', $payload)
            ->json();

        return (int) ($this->getData($response)['result']['pid'] ?? 0);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getExecStatus(int $pid): GuestAgentExecStatusData
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/agent/exec-status', ['pid' => $pid])
            ->json();

        return GuestAgentExecStatusData::fromRaw($this->getData($response));
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fileRead(string $file): string
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/agent/file-read', ['file' => $file])
            ->json();

        // Proxmox returns content (potentially truncated)
        return $this->getData($response)['result']['content'] ?? '';
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fileWrite(string $file, string $content, bool $encode = true): void
    {
        $payload = [
            'file' => $file,
            'content' => $content,
            'encode' => $encode ? 1 : 0,
        ];

        $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/agent/file-write', $payload)
            ->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function setUserPassword(string $username, string $password, bool $crypted = false): void
    {
        $payload = [
            'username' => $username,
            'password' => $password,
            'crypted' => $crypted ? 1 : 0,
        ];

        $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/agent/set-user-password', $payload)
            ->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function ping(): void
    {
        $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/agent/ping')
            ->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function shutdown(): void
    {
        $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/agent/shutdown')
            ->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fstrim(): void
    {
         $this->getHttpClientWithParams()
            ->post('/api2/json/nodes/{node}/qemu/{server}/agent/fstrim')
            ->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getTime(): int
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-time')
            ->json();

        return (int) ($this->getData($response)['result'] ?? 0);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getTimezone(): string
    {
        $response = $this->getHttpClientWithParams()
            ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-timezone')
            ->json();

        // get-timezone returns { "zone": "UTC", "offset": 0 }
        $data = $this->getData($response)['result'] ?? [];
        return $data['zone'] ?? '';
    }
}
