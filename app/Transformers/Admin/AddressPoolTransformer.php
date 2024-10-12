<?php

namespace App\Transformers\Admin;

use App\Models\AddressPool;
use League\Fractal\TransformerAbstract;
use League\Fractal\Resource\Collection;

class AddressPoolTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['addresses'];

    public function transform(AddressPool $pool): array
    {
        return [
            'id' => $pool->id,
            'name' => $pool->name,
            'nodes_count' => (int) $pool->nodes_count,
            'addresses_count' => (int) $pool->addresses_count,
        ];
    }

    public function includeAddresses(AddressPool $pool): Collection
    {
        return $this->collection($pool->addresses, new AddressTransformer());
    }
}
