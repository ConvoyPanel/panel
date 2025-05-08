<?php

namespace App\Transformers\Admin;

use App\Models\AddressBlock;
use League\Fractal\TransformerAbstract;

class AddressBlockTransformer extends TransformerAbstract
{
    public function transform(AddressBlock $block): array
    {
        return [
            'id' => $block->id,
            'address_block_group_id' => $block->address_block_group_id,
            'name' => $block->name,
            'description' => $block->description,
            'type' => $block->type,
            'base_ip' => $block->base_ip,
            'gateway' => $block->gateway,
            'mac_address' => $block->mac_address,
            'prefix_length_from' => $block->prefix_length_from,
            'prefix_length_to' => $block->prefix_length_to,
        ];
    }
}
