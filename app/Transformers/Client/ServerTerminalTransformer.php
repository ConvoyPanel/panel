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
    public function transform(array $data): array
    {
        return [
            'token' => array_get($data, 'token'),
            'node' => array_get($data, 'node'),
            'vmid' => array_get($data, 'vmid'),
            'fqdn' => array_get($data, 'fqdn'),
            'port' => array_get($data, 'port'),
        ];
    }
}
