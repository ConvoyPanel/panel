<?php

namespace Convoy\Transformers\Client;

use Convoy\Models\Server;
use League\Fractal\TransformerAbstract;

class RenamedServerTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Server $server): array
    {
        return [
            'name' => $server->name,
            'hostname' => $server->hostname,
        ];
    }
}
