<?php

namespace Convoy\Transformers\Client;

use Convoy\Data\Server\Proxmox\ServerStateData;
use League\Fractal\TransformerAbstract;

class ServerStateTransformer extends TransformerAbstract
{
    public function transform(ServerStateData $data)
    {
        return $data->toArray();
    }
}
