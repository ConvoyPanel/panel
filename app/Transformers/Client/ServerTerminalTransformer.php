<?php

namespace App\Transformers\Client;

use League\Fractal\TransformerAbstract;

class ServerTerminalTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     */
    public function transform(array $data): array
    {
        return [
            'ticket' => array_get($data, 'ticket'),
            'node' => array_get($data, 'node'),
            'vmid' => array_get($data, 'vmid'),
            'fqdn' => array_get($data, 'fqdn'),
            'port' => array_get($data, 'port'),
        ];
    }
}
