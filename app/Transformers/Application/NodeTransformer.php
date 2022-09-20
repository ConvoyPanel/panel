<?php

namespace Convoy\Transformers\Application;

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
            'port' => (int) $node->port,
        ];
    }
}
