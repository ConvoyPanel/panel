<?php

namespace App\Transformers\Admin;

use App\Models\NetworkInterface;
use League\Fractal\TransformerAbstract;

class NetworkInterfaceTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = ['node'];

    public function transform(NetworkInterface $interface): array
    {
        return [
            'id' => $interface->id,
            'node_id' => $interface->node_id,
            'name' => $interface->name,
            'description' => $interface->description,
        ];
    }

    public function includeNode(NetworkInterface $interface)
    {
        return $this->item($interface->node, new NodeTransformer);
    }
}
