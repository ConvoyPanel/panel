<?php

namespace App\Data\Cluster;

use Illuminate\Support\Collection;

/** The node and guest rows decoded from one `/cluster/resources` response. */
final readonly class ClusterResourceSnapshot
{
    /**
     * @param  Collection<int, NodeResourceData>  $nodes
     * @param  Collection<int, ServerResourceData>  $servers
     */
    public function __construct(
        public Collection $nodes,
        public Collection $servers,
    ) {}
}
