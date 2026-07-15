<?php

namespace App\Data\Node;

use App\Data\Server\OveragePenaltyData;
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
        public bool $verifyTls,
        public string $fqdn,
        public int $port,
        public int $socketCount,
        public int $coreCount,
        public int $cpuCount,
        public int $memory,
        public int $memoryOverallocate,
        public int $memoryAllocated,
        public ?int $cotermId,
        public int $serversCount,
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
            verifyTls: $node->verify_tls,
            fqdn: $node->fqdn,
            port: $node->port,
            socketCount: $node->socket_count,
            coreCount: $node->core_count,
            cpuCount: $node->cpu_count,
            memory: (int) $node->memory,
            memoryOverallocate: $node->memory_overallocate,
            memoryAllocated: (int) ($node->memory_allocated ?? 0),
            cotermId: $node->coterm_id,
            serversCount: (int) ($node->servers_count ?? 0),
            overagePenalty: $node->overage_penalty,
            defaultOveragePenalty: app(OveragePenaltyResolver::class)->global(),
        );
    }
}
