<?php

namespace App\Transformers\Admin;

use App\Data\Ipam\GeneratedAddressesData;
use League\Fractal\TransformerAbstract;

class GeneratedAddressesResultTransformer extends TransformerAbstract
{
    public function transform(GeneratedAddressesData $data): array
    {
        return $data->toArray();
    }
}
