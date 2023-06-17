<?php

namespace Convoy\Transformers\Admin;

use Convoy\Data\Server\Eloquent\AddressData;
use Convoy\Models\Address;
use League\Fractal\TransformerAbstract;

class AddressTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'server',
    ];

    public function transform(Address $address)
    {
        return AddressData::from($address->toArray())->toArray();
    }

    public function includeServer(Address $address)
    {
        return ! is_null($address->server) ? $this->item($address->server, new ServerTransformer()) : null;
    }
}
