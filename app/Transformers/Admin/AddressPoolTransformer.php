<?php

namespace Convoy\Transformers\Admin;

use Convoy\Models\AddressPool;
use League\Fractal\TransformerAbstract;

class AddressPoolTransformer extends TransformerAbstract
{
    public function transform(AddressPool $pool): array
    {
        return [
            'id' => $pool->id,
            'name' => $pool->name,
            'nodes_count' => (int) $pool->nodes_count,
            'addresses_count' => (int) $pool->addresses_count,
        ];
    }
}
