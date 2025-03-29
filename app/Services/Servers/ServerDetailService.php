<?php

namespace App\Services\Servers;

use App\Data\Server\Eloquent\ServerEloquentData;
use App\Models\Server;

class ServerDetailService
{
    public function __construct(private NetworkService $networkService)
    {
    }

    public function getByEloquent(Server $server): ServerEloquentData
    {
        $addresses = $this->networkService->getAddresses($server);

        return ServerEloquentData::from([
            'id' => $server->id,
            'uuid_short' => $server->uuid_short,
            'uuid' => $server->uuid,
            'node_id' => $server->node_id,
            'hostname' => $server->hostname,
            'name' => $server->name,
            'description' => $server->description,
            'status' => $server->status,
            'usages' => [
                'bandwidth' => $server->bandwidth_usage,
            ],
            'limits' => [
                'cpu' => $server->cpu,
                'memory' => $server->memory,
                'disk' => $server->disk,
                'snapshots' => $server->snapshot_limit,
                'backups' => $server->backup_limit,
                'bandwidth' => $server->bandwidth_limit,
                'addresses' => $addresses,
                'mac_address' => $this->networkService->getMacAddresses($server)->eloquent,
            ],
        ]);
    }
}
