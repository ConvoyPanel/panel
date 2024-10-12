<?php

namespace App\Transformers\Admin;

use App\Models\Server;
use Illuminate\Support\Facades\App;
use League\Fractal\TransformerAbstract;
use App\Services\Servers\ServerDetailService;

class ServerBuildTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'user',
        'node',
    ];

    public function transform(Server $server)
    {
        $serverEloquentData = App::make(ServerDetailService::class)->getByEloquent($server);

        $data = $serverEloquentData->toArray();

        $data['node_id'] = $server->node_id;
        $data['user_id'] = $server->user_id;
        $data['vmid'] = $server->vmid;
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
