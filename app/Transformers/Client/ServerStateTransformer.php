<?php

namespace App\Transformers\Client;

use League\Fractal\TransformerAbstract;
use App\Data\Server\Proxmox\ServerStateData;

class ServerStateTransformer extends TransformerAbstract
{
    public function transform(ServerStateData $data)
    {
        return $data->toArray();
    }
}
