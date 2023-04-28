<?php

namespace Convoy\Transformers\Client;

use League\Fractal\TransformerAbstract;

class ServerNetworkTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(array $data): array
    {
        return [
            'nameservers' => $data['nameservers'],
        ];
    }
}
