<?php

namespace App\Services\Nodes;

use App\Data\Cluster\ClusterResourceSnapshot;
use App\Data\Cluster\NodeResourceData;
use App\Data\Cluster\ServerResourceData;
use App\Enums\Node\NodeStatus;
use App\Enums\Node\Testing\ConnectionErrorCode;
use App\Exceptions\Proxmox\RequestException as ConvoyRequestException;
use App\Models\Node;
use App\Services\Proxmox\Cluster\ProxmoxResourceClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;

/**
 * Records whether Convoy can currently reach a node.
 *
 * Called from the scheduler rather than from a request: reading live state per
 * page render costs one PVE call per row, and an unreachable node burns the
 * full connect timeout rather than failing fast, so a listing would pay
 * `timeout x rows`. The read path only ever reads what this has written.
 *
 * The check is the authenticated API call, not an ICMP ping (which #104
 * proposed). A ping proves a NIC answered; it does not prove Proxmox is up, the
 * token is still valid, or the certificate is trusted. A host that pings while
 * answering `token_invalid` is down as far as Convoy is concerned.
 *
 * The call is `/cluster/resources`, which answers both questions Convoy has
 * about a node in one response: can we reach it, and what is each of its guests
 * doing. That is why it is this endpoint rather than `/nodes/{node}/status` --
 * the guest states come free, and a second endpoint would mean a second timeout
 * to sit through on a node that is down.
 *
 * See docs/node-status-plan.md.
 */
class NodeStatusPollService
{
    public function __construct(
        private ProxmoxResourceClient $client,
        private GuestStateCache $guestStates,
        private NodeResourceSnapshotCache $resourceSnapshots,
    ) {}

    public function handle(Node $node): NodeStatus
    {
        try {
            $resources = $this->client->setNode($node)->getResourceSnapshot();
        } catch (ConvoyRequestException|GuzzleRequestException|ConnectionException $e) {
            // The guest map is deliberately left to expire on its own rather
            // than being forgotten here. Until it lapses it is still the last
            // thing we actually observed, and a single failed poll is not
            // evidence that anything changed state.
            return $this->markUnreachable($node, $e->getMessage());
        }

        $this->guestStates->put($node, $this->mapGuestStates($node, $resources->servers));

        $resource = $this->findNodeResource($node, $resources);
        if ($resource !== null) {
            $this->resourceSnapshots->put($node, $resource);
        }

        return $this->markOnline($node);
    }

    private function findNodeResource(Node $node, ClusterResourceSnapshot $resources): ?NodeResourceData
    {
        return $resources->nodes->first(
            fn (NodeResourceData $resource) => $resource->nodeName === $node->name,
        );
    }

    /**
     * @param  Collection<int, ServerResourceData>  $guests
     * @return array<int, string> vmid => PVE status string
     */
    private function mapGuestStates(Node $node, Collection $guests): array
    {
        return $guests
            // On a real cluster this endpoint answers for every member, not
            // just the host we asked. Without this filter another node's guests
            // would be recorded against this one -- and vmids are only unique
            // per cluster, so the collision is silent.
            ->filter(fn (ServerResourceData $guest) => $guest->nodeName === $node->name)
            ->mapWithKeys(fn (ServerResourceData $guest) => [$guest->vmid => $guest->status])
            ->all();
    }

    private function markOnline(Node $node): NodeStatus
    {
        $node->forceFill([
            'status' => NodeStatus::ONLINE,
            'status_code' => null,
            'status_message' => null,
            'last_seen_at' => now(),
            'status_checked_at' => now(),
            'consecutive_failures' => 0,
        ])->save();

        return NodeStatus::ONLINE;
    }

    private function markUnreachable(Node $node, string $message): NodeStatus
    {
        $node->forceFill([
            'status' => NodeStatus::UNREACHABLE,
            // Classified through the same vocabulary the connection test and
            // NodeUnreachableException use, so "why is this node unhappy" reads
            // identically wherever it surfaces.
            'status_code' => ConnectionErrorCode::classify($message),
            'status_message' => $message,
            'status_checked_at' => now(),
            // last_seen_at deliberately untouched: it records the last time the
            // node actually answered, which is what staleness is measured from.
            'consecutive_failures' => $node->consecutive_failures + 1,
        ])->save();

        return NodeStatus::UNREACHABLE;
    }
}
