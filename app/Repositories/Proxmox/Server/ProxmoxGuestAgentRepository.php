<?php

namespace App\Repositories\Proxmox\Server;

use App\Data\Server\Proxmox\GuestAgent\GuestAgentExecStatusData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentFsInfoData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentInfoData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentNetworkInterfaceData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentOsInfoData;
use App\Data\Server\Proxmox\GuestAgent\GuestAgentUserData;
use App\Exceptions\Http\Server\Proxmox\GuestAgentUnavailableException;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class ProxmoxGuestAgentRepository extends ProxmoxRepository
{
    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function info(): GuestAgentInfoData
    {
        return $this->withGuestAgentHandler(function () {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/qemu/{server}/agent/info')
                ->json();

            return GuestAgentInfoData::fromRaw($this->getData($response));
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getOsInfo(): GuestAgentOsInfoData
    {
        return $this->withGuestAgentHandler(function () {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-osinfo')
                ->json();

            return GuestAgentOsInfoData::fromRaw($this->getData($response));
        });
    }

    /**
     * @return Collection<int, GuestAgentNetworkInterfaceData>
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getNetworkInterfaces(): Collection
    {
        return $this->withGuestAgentHandler(function () {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/qemu/{server}/agent/network-get-interfaces')
                ->json();

            // The result is usually in 'result' key
            $data = $this->getData($response)['result'] ?? [];

            return GuestAgentNetworkInterfaceData::collect($data, Collection::class);
        });
    }

    /**
     * @return Collection<int, GuestAgentFsInfoData>
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getFsInfo(): Collection
    {
        return $this->withGuestAgentHandler(function () {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-fsinfo')
                ->json();

            $data = $this->getData($response)['result'] ?? [];

            return GuestAgentFsInfoData::collect($data, Collection::class);
        });
    }

    /**
     * @return Collection<int, GuestAgentUserData>
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getUsers(): Collection
    {
        return $this->withGuestAgentHandler(function () {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-users')
                ->json();

            $data = $this->getData($response)['result'] ?? [];

            return GuestAgentUserData::collect($data, Collection::class);
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function exec(array $command, ?string $input = null): int
    {
        return $this->withGuestAgentHandler(function () use ($command, $input) {
            $payload = ['command' => $command];
            if ($input !== null) {
                $payload['input-data'] = $input;
            }

            $response = $this->getHttpClientWithParams()
                ->post('/api2/json/nodes/{node}/qemu/{server}/agent/exec', $payload)
                ->json();

            return (int) ($this->getData($response)['result']['pid'] ?? 0);
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getExecStatus(int $pid): GuestAgentExecStatusData
    {
        return $this->withGuestAgentHandler(function () use ($pid) {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/qemu/{server}/agent/exec-status', ['pid' => $pid])
                ->json();

            return GuestAgentExecStatusData::fromRaw($this->getData($response));
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fileRead(string $file): string
    {
        return $this->withGuestAgentHandler(function () use ($file) {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/qemu/{server}/agent/file-read', ['file' => $file])
                ->json();

            // Proxmox returns content (potentially truncated)
            return $this->getData($response)['result']['content'] ?? '';
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fileWrite(string $file, string $content, bool $encode = true): void
    {
        $this->withGuestAgentHandler(function () use ($file, $content, $encode) {
            $payload = [
                'file' => $file,
                'content' => $content,
                'encode' => $encode ? 1 : 0,
            ];

            $this->getHttpClientWithParams()
                ->post('/api2/json/nodes/{node}/qemu/{server}/agent/file-write', $payload)
                ->json();
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function setUserPassword(string $username, string $password, bool $crypted = false): void
    {
        $this->withGuestAgentHandler(function () use ($username, $password, $crypted) {
            $payload = [
                'username' => $username,
                'password' => $password,
                'crypted' => $crypted ? 1 : 0,
            ];

            $this->getHttpClientWithParams()
                ->post('/api2/json/nodes/{node}/qemu/{server}/agent/set-user-password', $payload)
                ->json();
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function ping(): void
    {
        $this->withGuestAgentHandler(function () {
            $this->getHttpClientWithParams()
                ->post('/api2/json/nodes/{node}/qemu/{server}/agent/ping')
                ->json();
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function shutdown(): void
    {
        $this->withGuestAgentHandler(function () {
            $this->getHttpClientWithParams()
                ->post('/api2/json/nodes/{node}/qemu/{server}/agent/shutdown')
                ->json();
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fstrim(): void
    {
        $this->withGuestAgentHandler(function () {
            $this->getHttpClientWithParams()
                ->post('/api2/json/nodes/{node}/qemu/{server}/agent/fstrim')
                ->json();
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getTime(): int
    {
        return $this->withGuestAgentHandler(function () {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-time')
                ->json();

            return (int) ($this->getData($response)['result'] ?? 0);
        });
    }

    /**
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getTimezone(): string
    {
        return $this->withGuestAgentHandler(function () {
            $response = $this->getHttpClientWithParams()
                ->get('/api2/json/nodes/{node}/qemu/{server}/agent/get-timezone')
                ->json();

            // get-timezone returns { "zone": "UTC", "offset": 0 }
            $data = $this->getData($response)['result'] ?? [];
            return $data['zone'] ?? '';
        });
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     * @throws GuestAgentUnavailableException
     * @throws RequestException
     * @throws ConnectionException
     */
    protected function withGuestAgentHandler(callable $callback)
    {
        try {
            return $callback();
        } catch (RequestException $e) {
            $response = $e->response->json();
            $message = $response['message'] ?? $e->getMessage();

            if (is_string($message)) {
                if (str_contains($message, 'QEMU guest agent is not running')) {
                    throw new GuestAgentUnavailableException('The QEMU Guest Agent is not running on this server. Please ensure it is installed and running.', $e);
                }

                if (preg_match('/VM \d+ is not running/', $message)) {
                    throw new GuestAgentUnavailableException('The server is not running. Please start the server to perform this action.', $e);
                }
            }

            throw $e;
        }
    }
}
