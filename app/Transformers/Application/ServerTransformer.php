<?php

namespace Convoy\Transformers\Application;

use Convoy\Models\Server;
use League\Fractal\TransformerAbstract;

class ServerTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Server $server)
    {
        return [
            'id' => $server->id,
            'vmid' => $server->vmid,
            'installing' => $server->installing,
            'name' => $server->name,
            'user_id' => $server->user_id,
            'node_id' => $server->node_id,
        ];
    }
}
