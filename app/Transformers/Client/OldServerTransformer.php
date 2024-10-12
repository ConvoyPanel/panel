<?php

namespace App\Transformers\Client;

use App\Models\Server;
use App\Services\Servers\ServerDetailService;
use App\Transformers\Admin\NodeTransformer;
use App\Transformers\Admin\UserTransformer;
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
