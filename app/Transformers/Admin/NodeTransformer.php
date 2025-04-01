<?php

namespace App\Transformers\Admin;

use App\Models\Node;
use League\Fractal\TransformerAbstract;

class NodeTransformer extends TransformerAbstract
{
    public function transform(Node $node): array
    {
        return [
            'id' => $node->id,
            'location_id' => $node->location_id,
            'display_name' => $node->display_name,
            'name' => $node->name,
            'verify_tls' => $node->verify_tls,
            'fqdn' => $node->fqdn,
            'port' => $node->port,
            'socket_count' => $node->socket_count,
            'core_count' => $node->core_count,
            'cpu_count' => $node->cpu_count,
            'memory' => $node->memory,
            'memory_overallocate' => $node->memory_overallocate,
            'memory_allocated' => $node->memory_allocated,
            'coterm_id' => $node->coterm_id,
            'servers_count' => (int) $node->servers_count,
        ];
    }
}
