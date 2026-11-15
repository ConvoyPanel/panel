<?php

namespace Convoy\Transformers\Client;

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
            // An ISO deleted from the library that is still in one of the server's drives.
            'missing' => $data['missing'] ?? false,
            'interface' => $data['interface'] ?? null,
        ];
    }
}
