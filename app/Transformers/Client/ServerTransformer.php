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
            'bandwidth_limit' => $server->bandwidth_limit,
        ];
    }
}
