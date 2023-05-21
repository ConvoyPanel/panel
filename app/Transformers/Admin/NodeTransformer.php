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
            'memory_allocated' => $node->memory_allocated,
            'disk' => $node->disk,
            'disk_overallocate' => $node->disk_overallocate,
            'disk_allocated' => $node->disk_allocated,
            'vm_storage' => $node->vm_storage,
            'backup_storage' => $node->backup_storage,
            'iso_storage' => $node->iso_storage,
            'network' => $node->network,
            'coterm_enabled' => $node->coterm_enabled,
            'coterm_tls_enabled' => $node->coterm_tls_enabled,
            'coterm_fqdn' => $node->coterm_fqdn,
            'coterm_port' => $node->coterm_port,
            'servers_count' => (int) $node->servers_count,
        ];
    }
}
