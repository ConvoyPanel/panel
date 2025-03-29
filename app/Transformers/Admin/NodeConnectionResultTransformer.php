<?php

namespace App\Transformers\Admin;

use League\Fractal\TransformerAbstract;
use App\Data\Node\Testing\ConnectionResultData;

class NodeConnectionResultTransformer extends TransformerAbstract
{
    public function transform(ConnectionResultData $data): array
    {
        return $data->toArray();
    }
}
