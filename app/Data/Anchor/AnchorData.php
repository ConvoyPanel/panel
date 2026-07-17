<?php

namespace App\Data\Anchor;

use App\Enums\Anchor\AnchorCompatibility;
use App\Enums\Anchor\AnchorMode;
use App\Models\Anchor;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class AnchorData extends Data
{
    /** @param array<int, string> $capabilities */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public AnchorMode $mode,
        public string $publicUrl,
        public ?int $relayId,
        public int $nodesCount,
        public int $agentsCount,
        public ?string $enrolledAt,
        public ?string $lastSeenAt,
        public ?string $version,
        public ?int $protocolMin,
        public ?int $protocolMax,
        public array $capabilities,
        public AnchorCompatibility $compatibility,
    ) {}

    public static function fromModel(Anchor $anchor): self
    {
        return new self(
            id: $anchor->id,
            uuid: $anchor->uuid,
            name: $anchor->name,
            mode: $anchor->mode,
            publicUrl: $anchor->public_url,
            relayId: $anchor->relay_id,
            nodesCount: (int) ($anchor->nodes_count ?? 0),
            agentsCount: (int) ($anchor->agents_count ?? 0),
            enrolledAt: $anchor->enrolled_at?->toIso8601String(),
            lastSeenAt: $anchor->last_seen_at?->toIso8601String(),
            version: $anchor->version,
            protocolMin: $anchor->protocol_min,
            protocolMax: $anchor->protocol_max,
            capabilities: $anchor->capabilities ?? [],
            compatibility: $anchor->compatibility(),
        );
    }
}
