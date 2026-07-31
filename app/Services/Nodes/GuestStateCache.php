<?php

namespace App\Services\Nodes;

use App\Enums\Server\State;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Support\Facades\Cache;

/**
 * The power state of each guest on a node, as of the last observation.
 *
 * A cache rather than columns -- the opposite of where node reachability lives,
 * deliberately. Reachability drives alerting, which needs a state machine that
 * survives a Redis restart; this is high-churn, non-critical, and one key per
 * node beats a row write per server every minute. See docs/node-status-plan.md.
 *
 * A miss is `unknown`, never `stopped`. Nothing here ever fetches on a miss:
 * the read path must not touch PVE, or one cold cache turns a server list back
 * into the `timeout x rows` stall the poller exists to prevent.
 *
 * Two writers, at different granularities:
 *
 *  - `put()` -- the every-minute node poll, one key holding the whole node's
 *    guest map. The bulk source of truth.
 *  - `observe()` -- a single guest, written through by whatever just read that
 *    guest's status live from PVE (see ProxmoxServerClient::getState). This is
 *    what keeps a server list truthful in the minute after a power action,
 *    without anyone having to invalidate anything.
 *
 * Write-through rather than invalidation on purpose. Dropping the node key
 * after a power action would take every *other* guest on that node down to
 * `unknown` until the next poll -- one user's reboot blanking everyone else's
 * dashboard -- because a miss here is never refilled on demand. Writing the
 * answer we already hold costs nothing and leaves no hole.
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
     *
     * Single-guest observations expire on the same clock, for the same reason:
     * they are the last thing anyone actually saw, and they stop being worth
     * standing behind at exactly the point a poll would have.
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

    public static function guestKeyForServerId(int $serverId): string
    {
        return "server:{$serverId}:power-state";
    }

    /**
     * Record a whole node's guest map, as of now.
     *
     * @param  array<int, string>  $states  vmid => PVE status string
     */
    public function put(Node $node, array $states): void
    {
        Cache::put(self::key($node), [
            'observed_at' => $this->nowMs(),
            'states' => $states,
        ], now()->addMinutes(self::TTL_MINUTES));
    }

    /**
     * Record one guest's state, as of now, from a live read of that guest.
     *
     * Deliberately a key of its own rather than a patch into the node map.
     * Rewriting the map would reset its expiry, and that expiry is load-bearing
     * -- it is the only thing that says "nobody has polled this node lately".
     * A power action must not be able to make a stale map look freshly polled.
     */
    public function observe(Server $server, State $state): void
    {
        Cache::put(self::guestKeyForServerId($server->id), [
            'observed_at' => $this->nowMs(),
            'state' => $state->value,
        ], now()->addMinutes(self::TTL_MINUTES));
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
        return $this->snapshotForNodeId($nodeId)['states'] ?? null;
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
     *
     * Where a single-guest observation and the node map disagree, the more
     * recently observed one wins -- in *either* direction. Preferring the
     * single-guest write unconditionally would be the obvious shortcut and is
     * wrong: a guest stopped outside Convoy after someone last watched it would
     * keep reading `running` off an observation the poller has since
     * superseded. Whichever fact was recorded later is the one that describes
     * the present.
     */
    public function stateFor(Server $server): ?State
    {
        // Keyed off `node_id` rather than the `node` relation on purpose: a
        // server list resolves this once per row, and touching the relation
        // would load a node per row to build a key it already has. The
        // observation timestamps live in the cached values for the same reason
        // -- comparing against `nodes.status_checked_at` would reintroduce
        // exactly that per-row load.
        $snapshot = $this->snapshotForNodeId($server->node_id);
        $observation = $this->observationFor($server);

        if ($observation === null) {
            return $this->stateFromSnapshot($snapshot, $server->vmid);
        }

        // Ties go to the single-guest read: it looked at this one guest
        // directly, where the poll answered for the whole node at once.
        if ($snapshot === null || $observation['observed_at'] >= $snapshot['observed_at']) {
            return State::tryFrom($observation['state']);
        }

        return $this->stateFromSnapshot($snapshot, $server->vmid);
    }

    /**
     * @param  array{observed_at: int, states: array<int, string>}|null  $snapshot
     */
    private function stateFromSnapshot(?array $snapshot, int $vmid): ?State
    {
        if ($snapshot === null || ! array_key_exists($vmid, $snapshot['states'])) {
            return null;
        }

        return State::tryFrom($snapshot['states'][$vmid]);
    }

    /**
     * @return array{observed_at: int, states: array<int, string>}|null
     */
    private function snapshotForNodeId(int $nodeId): ?array
    {
        $record = Cache::get(self::keyForNodeId($nodeId));

        if (! is_array($record)) {
            return null;
        }

        // A bare vmid => status map is what an older release wrote, with no
        // observation time attached. Dated to the epoch so that any timestamped
        // observation outranks it, rather than guessing an age for it; the
        // ambiguity lasts only the minute it takes the next poll to overwrite.
        if (! array_key_exists('states', $record)) {
            return ['observed_at' => 0, 'states' => $record];
        }

        return ['observed_at' => (int) $record['observed_at'], 'states' => $record['states']];
    }

    /**
     * @return array{observed_at: int, state: string}|null
     */
    private function observationFor(Server $server): ?array
    {
        $record = Cache::get(self::guestKeyForServerId($server->id));

        if (! is_array($record) || ! isset($record['state'], $record['observed_at'])) {
            return null;
        }

        return ['observed_at' => (int) $record['observed_at'], 'state' => (string) $record['state']];
    }

    /**
     * Milliseconds, not seconds: the poll and a live read can land inside the
     * same second, and at second resolution the tie-break would decide which
     * fact wins far more often than it should.
     */
    private function nowMs(): int
    {
        return now()->getTimestampMs();
    }
}
