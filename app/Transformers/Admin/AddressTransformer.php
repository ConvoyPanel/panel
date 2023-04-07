<?php

namespace Convoy\Transformers\Admin;

use Convoy\Data\Server\Eloquent\AddressData;
use Convoy\Models\IPAddress;
use League\Fractal\TransformerAbstract;

class AddressTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'server'
    ];

    public function transform(IPAddress $address)
    {
        return AddressData::from($address->toArray())->toArray();
    }

    public function includeServer(IPAddress $address)
    {
        return !is_null($address->server) ? $this->item($address->server, new ServerTransformer) : null;
    }
}
