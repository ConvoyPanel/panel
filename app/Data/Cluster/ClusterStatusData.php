<?php

namespace App\Data\Cluster;

/**
 * What one `/cluster/status` response said: the cluster's display name (null
 * on a standalone host -- PVE expresses "not clustered" by omitting the
 * `type=cluster` row, not by an error) and the member node names it listed.
 */
final readonly class ClusterStatusData
{
    /**
     * @param  array<int, string>  $memberNames
     */
    public function __construct(
        public ?string $clusterName,
        public array $memberNames,
    ) {}
}
