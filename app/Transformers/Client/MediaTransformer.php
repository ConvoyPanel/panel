<?php

namespace App\Transformers\Client;

use League\Fractal\TransformerAbstract;

class MediaTransformer extends TransformerAbstract
{
    public function transform(array $data)
    {
        return [
            'uuid' => $data['uuid'],
            'name' => $data['name'],
            'size' => $data['size'],
            'hidden' => $data['hidden'],
            'mounted' => $data['mounted'],
        ];
    }
}
