<?php

namespace App\Data\Node;

use App\Data\Server\OveragePenaltyData;
use App\Enums\Node\ConnectionErrorCode;
use App\Enums\Node\NodeStatus;
use App\Models\Node;
use App\Services\Servers\OveragePenaltyResolver;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class NodeData extends Data
{
    public function __construct(
        public int $id,
        public int $locationId,
        public string $displayName,
        public string $name,
        /**
         * The PVE cluster this host belongs to, or null when it is standalone.
         *
         * Discovered by the poll. Standalone is a real answer, not a gap -- and
         * it is the one that explains why a node is offered nothing to attach.
         */
        public ?string $clusterName,
        public bool $verifyTls,
        public string $fqdn,
        public int $port,
        public int $socketCount,
        public int $coreCount,
        public int $cpuCount,
        public int $memory,
        public int $memoryOverallocate,
        public int $memoryAllocated,
        public ?int $anchorId,
        public int $serversCount,
        /**
         * Reachability as of {@see $statusCheckedAt}, written by `nodes:poll`
         * and degraded to `unknown` once too stale to trust. Never read live
         * per request: see docs/node-status-plan.md.
         */
        public NodeStatus $status = NodeStatus::UNKNOWN,
        /** Why it is unreachable, in the connection test's vocabulary. */
        public ?ConnectionErrorCode $statusCode = null,
        /** Last *successful* contact, so the UI can say how stale this is. */
        public ?string $lastSeenAt = null,
        public ?string $statusCheckedAt = null,
        /**
         * This node's override of the quota-overage penalty. Null = inherit the
         * global tier, which is what {@see $defaultOveragePenalty} carries.
         */
        public ?OveragePenaltyData $overagePenalty = null,
        /**
         * The global-tier default this node falls back to when it has no
         * override. Sent so the settings UI can show the resolved *effective*
         * value while the field is left on "Inherit"; it is read-only here and
         * is edited on the global Settings screen.
         */
        public ?OveragePenaltyData $defaultOveragePenalty = null,
    ) {}

    public static function fromModel(Node $node): self
    {
        return new self(
            id: $node->id,
            locationId: $node->location_id,
            displayName: $node->display_name,
            name: $node->name,
            clusterName: $node->cluster_name,
            verifyTls: $node->verify_tls,
            fqdn: $node->fqdn,
            port: $node->port,
            socketCount: $node->socket_count,
            coreCount: $node->core_count,
            cpuCount: $node->cpu_count,
            memory: (int) $node->memory,
            memoryOverallocate: $node->memory_overallocate,
            memoryAllocated: (int) ($node->memory_allocated ?? 0),
            anchorId: $node->anchor_id,
            serversCount: (int) ($node->servers_count ?? 0),
            status: $node->currentStatus(),
            // Only meaningful alongside a live `unreachable`; a stale row keeps
            // its last code in the database, but sending it with an `unknown`
            // status would invite the UI to explain a failure it cannot vouch for.
            statusCode: $node->currentStatus() === NodeStatus::UNREACHABLE
                ? $node->status_code
                : null,
            lastSeenAt: $node->last_seen_at?->toIso8601String(),
            statusCheckedAt: $node->status_checked_at?->toIso8601String(),
            overagePenalty: $node->overage_penalty,
            defaultOveragePenalty: app(OveragePenaltyResolver::class)->global(),
        );
    }
}
