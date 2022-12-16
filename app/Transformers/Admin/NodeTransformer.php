<?php

namespace Convoy\Transformers\Admin;

use Convoy\Models\Node;
use League\Fractal\TransformerAbstract;

class NodeTransformer extends TransformerAbstract
{
    public function transform(Node $node): array
    {
        return [
            'id' => $node->id,
            'location_id' => $node->location_id,
            'name' => $node->name,
            'cluster' => $node->cluster,
            'fqdn' => $node->fqdn,
            'port' => $node->port,
            'memory' => $node->memory,
            'memory_overallocate' => $node->memory_overallocate,
            'disk' => $node->disk,
            'disk_overallocate' => $node->disk_overallocate,
            'vm_storage' => $node->vm_storage,
            'backup_storage' => $node->backup_storage,
            'network' => $node->network,
            'servers_count' => $node->servers_count
        ];
    }
}
