<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\Server;
use League\Fractal\TransformerAbstract;

class ServerTransformer extends TransformerAbstract
{
    public function transform(Server $server): array
    {
        return [
            'id' => $server->id,
            'uuid' => $server->uuid,
            'uuid_short' => $server->uuid_short,
            'user_id' => $server->user_id,
            'node_id' => $server->node_id,
            'vmid' => $server->vmid,
            'hostname' => $server->hostname,
            'name' => $server->name,
            'description' => $server->description,
            'status' => $server->status,
            'cpu' => $server->cpu,
            'memory' => $server->memory,
            'disk' => $server->disk,
            'bandwidth_usage' => $server->bandwidth_usage,
            'snapshot_count_limit' => $server->snapshot_count_limit,
            'snapshot_size_limit' => $server->snapshot_size_limit,
            'backup_count_limit' => $server->backup_count_limit,
            'backup_size_limit' => $server->backup_size_limit,
            'bandwidth_limit' => $server->bandwidth_limit,
            'created_at' => $server->created_at,
        ];
    }
}
