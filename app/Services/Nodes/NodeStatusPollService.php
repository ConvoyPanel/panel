<?php

namespace App\Services\Nodes;

use App\Enums\Node\NodeStatus;
use App\Enums\Node\Testing\ConnectionErrorCode;
use App\Exceptions\Proxmox\RequestException as ConvoyRequestException;
use App\Models\Node;
use App\Services\Proxmox\Node\ProxmoxStatusClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Http\Client\ConnectionException;

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
 * See docs/node-status-plan.md.
 */
class NodeStatusPollService
{
    public function __construct(private ProxmoxStatusClient $client) {}

    public function handle(Node $node): NodeStatus
    {
        try {
            $this->client->setNode($node)->getStatus();
        } catch (ConvoyRequestException|GuzzleRequestException|ConnectionException $e) {
            return $this->markUnreachable($node, $e->getMessage());
        }

        return $this->markOnline($node);
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
