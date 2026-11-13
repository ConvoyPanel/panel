<?php

namespace App\Enums\Server;

/**
 * How a guest actually gets from one node to the other.
 *
 * Derived from where the two nodes sit, never chosen by the operator: the
 * panel knows whether the destination is a member of the source's cluster, and
 * that fact settles the question. Surfacing it is still worth doing, because
 * the two transports cost very different things.
 */
enum MigrationTransport: string
{
    /**
     * `qm migrate` between members of one PVE cluster. Live when the addresses
     * follow, and the only transport that can keep a guest running.
     */
    case Cluster = 'cluster';

    /**
     * `vzdump` on the source, a direct node-to-node download, and `qmrestore`
     * on the destination, driven over Anchor. Offline for the whole transfer:
     * there is no supported live migration between separate clusters, and the
     * two candidates are refused for the reasons recorded in
     * docs/migration-anchor-contract.md.
     */
    case Anchor = 'anchor';
}
