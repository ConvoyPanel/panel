<?php

namespace Convoy\Transformers\Admin;

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
        ];
    }
}
