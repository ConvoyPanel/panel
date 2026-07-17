<?php

namespace App\Services\Nodes;

use App\Enums\Server\State;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;

/**
 * The power state of each guest on a node, as of the last poll.
 *
 * A cache rather than columns -- the opposite of where node reachability lives,
 * deliberately. Reachability drives alerting, which needs a state machine that
 * survives a Redis restart; this is high-churn, non-critical, and one key per
 * node beats a row write per server every minute. See docs/node-status-plan.md.
 *
 * A miss is `unknown`, never `stopped`. Nothing here ever fetches on a miss:
 * the read path must not touch PVE, or one cold cache turns a server list back
 * into the `timeout x rows` stall the poller exists to prevent.
 */
class GuestStateCache
{
    /**
     * Kept in step with `Node::STATUS_TTL_MINUTES` rather than the plan's
     * "2x the poll interval".
     *
     * The two facts have to expire together. A node whose row still reads
     * `online` while its guest map had already lapsed would render a list of
     * `unknown` guests under a healthy host -- an inconsistency the viewer can
     * only read as a bug. Both now go stale at the same moment, for the same
     * reason: nobody has polled lately.
     */
    public const TTL_MINUTES = Node::STATUS_TTL_MINUTES;

    public static function key(Node $node): string
    {
        return self::keyForNodeId($node->id);
    }

    public static function keyForNodeId(int $nodeId): string
    {
        return "node:{$nodeId}:vm-states";
    }

    /**
     * @param  array<int, string>  $states  vmid => PVE status string
     */
    public function put(Node $node, array $states): void
    {
        Cache::put(self::key($node), $states, now()->addMinutes(self::TTL_MINUTES));
    }

    /**
     * @return array<int, string>|null null when the node has not been polled
     *                                 recently enough to say
     */
    public function for(Node $node): ?array
    {
        return $this->forNodeId($node->id);
    }

    /**
     * @return array<int, string>|null
     */
    public function forNodeId(int $nodeId): ?array
    {
        return Cache::get(self::keyForNodeId($nodeId));
    }

    public function forget(Node $node): void
    {
        Cache::forget(self::key($node));
    }

    /**
     * The remembered state of one guest, or null for "we cannot say".
     *
     * Null covers both a node nobody has polled and a guest PVE did not
     * mention. The second is not the same as `stopped`: a guest missing from
     * `/cluster/resources` has usually been removed outside Convoy, and
     * answering `stopped` would invite someone to press Start on it.
     */
    public function stateFor(Server $server): ?State
    {
        // Keyed off `node_id` rather than the `node` relation on purpose: a
        // server list resolves this once per row, and touching the relation
        // would load a node per row to build a key it already has.
        $states = $this->forNodeId($server->node_id);

        if ($states === null || ! array_key_exists($server->vmid, $states)) {
            return null;
        }

        return State::tryFrom($states[$server->vmid]);
    }
}
