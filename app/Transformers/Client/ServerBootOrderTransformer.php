<?php

namespace App\Transformers\Client;

use League\Fractal\TransformerAbstract;

class ServerBootOrderTransformer extends TransformerAbstract
{
    public function transform(array $data)
    {
        return [
            'unused_devices' => $data['unused_devices']->toArray(),
            'boot_order' => $data['boot_order']->toArray(),
        ];
    }
}
