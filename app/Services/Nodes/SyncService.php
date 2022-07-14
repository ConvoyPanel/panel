<?php

namespace App\Services\Nodes;

use App\Models\Server;
use App\Services\ProxmoxService;

class SyncService extends ProxmoxService
{
    // WIP Function: Will sync node information with the database (disks, RAM, etc)
    public function syncNodeInformation($cluster = [], array $params = [])
    {
        $storage =  $this->instance($cluster)->nodes()->node()->storage()->get();
    }
}
