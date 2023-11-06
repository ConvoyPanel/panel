<?php

namespace Convoy\Transformers\Admin;

use Convoy\Models\Address;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Convoy\Data\Server\Eloquent\AddressData;

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
        return !is_null($address->server) ? $this->item($address->server, new ServerTransformer()) : null;
    }
}
