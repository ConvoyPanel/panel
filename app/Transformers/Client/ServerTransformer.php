<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\Server;
use Illuminate\Support\Facades\App;
use League\Fractal\TransformerAbstract;
use Convoy\Transformers\Admin\NodeTransformer;
use Convoy\Transformers\Admin\UserTransformer;
use Convoy\Services\Servers\ServerDetailService;

class ServerTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'user',
        'node',
    ];

    public function transform(Server $server)
    {
        $serverEloquentData = App::make(ServerDetailService::class)->getByEloquent($server);

        $data = $serverEloquentData->toArray();

        $data['internal_id'] = $data['id'];
        $data['id'] = $data['uuid_short'];
        unset($data['uuid_short']);

        return $data;
    }

    public function includeUser(Server $server)
    {
        return $this->item($server->user, new UserTransformer);
    }

    public function includeNode(Server $server)
    {
        return $this->item($server->node, new NodeTransformer);
    }
}
