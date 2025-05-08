<?php

namespace App\Transformers\Admin;

use App\Models\AddressBlockGroup;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;

class AddressBlockGroupTransformer extends TransformerAbstract
{
    public function transform(AddressBlockGroup $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'address_blocks_count' => (int) $group->address_blocks_count,
            'nodes_count' => (int) $group->nodes_count,
        ];
    }
}
