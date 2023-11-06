<?php

namespace Convoy\Transformers\Client;

use League\Fractal\TransformerAbstract;
use Convoy\Data\Server\Proxmox\ServerStateData;

class ServerStateTransformer extends TransformerAbstract
{
    public function transform(ServerStateData $data)
    {
        return $data->toArray();
    }
}
