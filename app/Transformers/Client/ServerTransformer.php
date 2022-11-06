<?php

namespace Convoy\Transformers\Client;

use Convoy\Data\Server\Eloquent\ServerEloquentData;
use Convoy\Enums\Network\AddressType;
use Convoy\Models\Server;
use League\Fractal\TransformerAbstract;

class ServerTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(ServerEloquentData $server)
    {
        return [
            'id' => $server->uuid_short,
            'internal_id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'description' => $server->description,
            'status' => $server->status,
            'node_id' => $server->node_id,
            'usages' => [
                'bandwidth' => $server->usages->bandwidth,
            ],
            'limits' => [
                'cpu' => $server->limits->cpu,
                'memory' => $server->limits->memory,
                'disk' => $server->limits->disk,
                'snapshots' => $server->limits->snapshots,
                'backups' => $server->limits->backups,
                'bandwidth' => $server->limits->bandwidth,
                'addresses' => [
                    'ipv4' => $server->limits->addresses->ipv4,
                    'ipv6' => $server->limits->addresses->ipv6
                ]
            ]
        ];
    }
}
