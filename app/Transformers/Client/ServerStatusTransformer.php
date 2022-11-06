<?php

namespace Convoy\Transformers\Client;

use League\Fractal\TransformerAbstract;

class ServerStatusTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(array $data)
    {
        return [
            'state' => array_get($data, 'status'),
            'uptime' => array_get($data, 'uptime'),
            'cpu' => array_get($data, 'cpu'),
            'memory' => array_get($data, 'mem'),
        ];
    }
}
