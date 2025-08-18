<?php

namespace App\Repositories\Proxmox\Server;

use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\Repository\Proxmox\RequestException;
use function array_pluck;

class ProxmoxActivityRepository extends ProxmoxRepository
{
    public function getTasks(int $startAt = 0, int $limitRows = 500)
    {
        $response = $this->getHttpClientWithParams()
            ->get(
                '/api2/json/nodes/{node}/tasks',
                [
                    'vmid' => $this->getServer()->vmid,
                    'start' => $startAt,
                    'limit' => $limitRows,
                ]
            )
            ->json();

        return $this->getData($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getStatus(string $upid)
    {
        $response = $this->getHttpClientWithParams([
            'task' => $upid,
        ])
            ->get('/api2/json/nodes/{node}/tasks/{task}/status')
            ->json();

        return $this->getData($response);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     *
     * @return string[]
     */
    public function getLogsByTask(string $upid, int $startAt = 0, int $limitLinesTo = 100): array
    {
        $response = $this->getHttpClientWithParams([
            'task' => $upid,
        ])
            ->get('/api2/json/nodes/{node}/tasks/{task}/log', [
                'start' => $startAt,
                'limit' => $limitLinesTo,
            ])
            ->json();

        return array_pluck($this->getData($response), 't');
    }

    public function delete(string $upid)
    {
        $response = $this->getHttpClientWithParams([
            'task' => $upid,
        ])
            ->delete('/api2/json/nodes/{node}/tasks/{task}')
            ->json();

        return $this->getData($response);
    }
}
