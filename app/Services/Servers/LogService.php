<?php

namespace App\Services\Servers;

use App\Services\ProxmoxService;

class LogService extends ProxmoxService
{
    public function fetchLogs(int $startAt = 0, int $limitRows = 500)
    {
        return $this->nodeInstance()->tasks()->get(['vmid' => $this->server->vmid, 'start' => $startAt, 'limit' => $limitRows]);
    }

    public function stopTask(string $upid)
    {
        return $this->nodeInstance()->tasks()->delete(['upid' => $upid]);
    }
}
