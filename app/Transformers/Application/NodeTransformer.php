<?php

namespace App\Transformers\Application;

use App\Models\Node;
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
            'auth_type' => $node->auth_type,
            'latency_in_ms' => $node->latency,
        ];
    }
}
