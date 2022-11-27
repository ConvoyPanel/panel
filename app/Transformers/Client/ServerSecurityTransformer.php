<?php

namespace Convoy\Transformers\Client;

use League\Fractal\TransformerAbstract;

class ServerSecurityTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(array $data)
    {
        return [
            'ssh_keys' => $data['ssh_keys'],
        ];
    }
}
