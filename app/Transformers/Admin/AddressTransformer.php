<?php

namespace App\Transformers\Admin;

use App\Models\Address;
use App\Transformers\Client\ServerTransformer;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;

class AddressTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'server',
        'addressBlock',
    ];

    public function transform(Address $address): array
    {
        return [
            'id' => $address->id,
            'address_block_id' => $address->address_block_id,
            'server_id' => $address->server_id,
            'version' => $address->version,
            'ip' => $address->ip,
            'prefix_length' => $address->prefix_length,
            'gateway' => $address->gateway,
            'mac_address' => $address->mac_address,
        ];
    }

    public function includeServer(Address $address): ?Item
    {
        return ! is_null($address->server) ? $this->item(
            $address->server,
            new ServerTransformer,
        ) : null;
    }

    public function includeAddressBlock(Address $address): ?Item
    {
        return $this->item($address->addressBlock, new AddressBlockTransformer);
    }
}
