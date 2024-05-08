<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\Server;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Transformers\Admin\NodeTransformer;
use Convoy\Transformers\Admin\UserTransformer;
use Illuminate\Support\Facades\App;
use League\Fractal\TransformerAbstract;

class OldServerTransformer extends TransformerAbstract
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
        return $this->item($server->user, new UserTransformer());
    }

    public function includeNode(Server $server)
    {
        return $this->item($server->node, new NodeTransformer());
    }
}
