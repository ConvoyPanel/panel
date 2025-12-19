<?php

namespace App\Transformers\Admin;

use App\Models\NetworkInterface;
use League\Fractal\TransformerAbstract;

class NetworkInterfaceTransformer extends TransformerAbstract
{
    public function transform(NetworkInterface $interface): array
    {
        return [
            'id' => $interface->id,
            'node_id' => $interface->node_id,
            'name' => $interface->name,
            'description' => $interface->description,
        ];
    }
}
