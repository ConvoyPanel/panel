<?php

namespace Convoy\Transformers\Admin;

use Convoy\Models\Location;
use League\Fractal\TransformerAbstract;

class LocationTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['nodes', 'servers'];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Location $location): array
    {
        return [
            'id' => $location->id,
            'short_code' => $location->short_code,
            'description' => $location->description,
            'nodes_count' => (int) $location->nodes_count,
            'servers_count' => (int) $location->servers_count,
        ];
    }
}
