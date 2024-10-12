<?php

namespace App\Transformers\Client;

use App\Data\Server\Proxmox\Usages\ServerTimepointData;
use League\Fractal\TransformerAbstract;

class ServerTimepointDataTransformer extends TransformerAbstract
{
    public function transform(ServerTimepointData $data): array
    {
        return $data->toArray();
    }
}
