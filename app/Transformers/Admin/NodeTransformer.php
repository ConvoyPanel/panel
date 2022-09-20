<?php

namespace Convoy\Transformers\Admin;

use Convoy\Models\Node;
use League\Fractal\TransformerAbstract;

class NodeTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Node $node)
    {
        return [
            'id' => $node->id,
            'name' => $node->name,
            'cluster' => $node->cluster,
            'hostname' => $node->hostname,
            'port' => $node->port,
            'latency' => $node->latency,
            'last_pinged' => $node->last_pinged,
            'created_at' => $node->created_at,
            'updated_at' => $node->updated_at,
        ];
    }
}
