<?php

namespace App\Transformers\Client;

use League\Fractal\TransformerAbstract;

class ServerSecurityTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     */
    public function transform(array $data): array
    {
        return [
            'ssh_keys' => $data['ssh_keys'],
        ];
    }
}
