<?php

namespace App\Transformers\Client;

use League\Fractal\TransformerAbstract;

class ServerNetworkTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     */
    public function transform(array $data): array
    {
        return [
            'nameservers' => $data['nameservers'],
        ];
    }
}
