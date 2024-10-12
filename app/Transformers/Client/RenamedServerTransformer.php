<?php

namespace App\Transformers\Client;

use App\Models\Server;
use League\Fractal\TransformerAbstract;

class RenamedServerTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     */
    public function transform(Server $server): array
    {
        return [
            'name' => $server->name,
            'hostname' => $server->hostname,
        ];
    }
}
