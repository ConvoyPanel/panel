<?php

namespace App\Data\Cluster;

use Illuminate\Support\Collection;

/** The node, guest and storage rows decoded from one `/cluster/resources` response. */
final readonly class ClusterResourceSnapshot
{
    /**
     * @param  Collection<int, NodeResourceData>  $nodes
     * @param  Collection<int, ServerResourceData>  $servers
     * @param  Collection<int, StorageResourceData>  $storages
     */
    public function __construct(
        public Collection $nodes,
        public Collection $servers,
        public Collection $storages,
    ) {}
}
