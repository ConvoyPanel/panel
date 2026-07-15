<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\Activity\TaskData;
use App\Data\Server\Proxmox\Activity\TaskLogData;
use App\Exceptions\Proxmox\RequestException;
use App\Services\Proxmox\ProxmoxClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

class ProxmoxActivityClient extends ProxmoxClient
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     * @return Collection<int, TaskData>
     */
    public function getTasks(int $startAt = 0, int $limitRows = 500): Collection
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

        return collect($this->getData($response))->map(
            fn(array $task) => TaskData::fromRaw($task)
        );
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getStatus(string $upid): TaskData
    {
        $response = $this->getHttpClientWithParams([
            'task' => $upid,
        ])
            ->get('/api2/json/nodes/{node}/tasks/{task}/status')
            ->json();

        return TaskData::fromRaw($this->getData($response));
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     *
     * @return Collection<int, TaskLogData>
     */
    public function getLogsByTask(string $upid, int $startAt = 0, int $limitLinesTo = 100): Collection
    {
        $response = $this->getHttpClientWithParams([
            'task' => $upid,
        ])
            ->get('/api2/json/nodes/{node}/tasks/{task}/log', [
                'start' => $startAt,
                'limit' => $limitLinesTo,
            ])
            ->json();

        return collect($this->getData($response))->map(
            fn(array $log) => TaskLogData::fromRaw($log)
        );
    }

    /**
     * Stops a running task
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function stop(string $upid): void
    {
        $this->getHttpClientWithParams([
            'task' => $upid,
        ])
            ->delete('/api2/json/nodes/{node}/tasks/{task}')
            ->json();
    }
}
