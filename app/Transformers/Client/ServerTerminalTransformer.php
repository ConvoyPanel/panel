<?php

namespace Convoy\Transformers\Client;

use League\Fractal\TransformerAbstract;

class ServerTerminalTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(array $data)
    {
        return [
            'token' => array_get($data, 'token'),
            'node' => array_get($data, 'node'),
            'vmid' => array_get($data, 'vmid'),
            'hostname' => array_get($data, 'hostname'),
            'port' => array_get($data, 'port'),
        ];
    }
}
