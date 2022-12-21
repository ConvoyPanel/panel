<?php

namespace Convoy\Transformers\Admin;

use Convoy\Data\Server\Eloquent\AddressData;
use Convoy\Models\IPAddress;
use League\Fractal\TransformerAbstract;

class AddressTransformer extends TransformerAbstract
{
    public function transform(IPAddress $address)
    {
        return AddressData::from($address->toArray())->toArray();
    }
}
