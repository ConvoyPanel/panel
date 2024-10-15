<?php

namespace App\Transformers\Admin;

use App\Data\Server\Eloquent\AddressData;
use App\Models\Address;
use App\Transformers\Client\ServerTransformer;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;

class AddressTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'server',
    ];

    public function transform(Address $address): array
    {
        return AddressData::from($address->toArray())->toArray();
    }

    public function includeServer(Address $address): ?Item
    {
        return ! is_null($address->server) ? $this->item(
            $address->server,
            new ServerTransformer(),
        ) : null;
    }
}
