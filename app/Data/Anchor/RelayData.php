<?php

namespace App\Data\Anchor;

use App\Enums\Anchor\AnchorCompatibility;
use App\Models\Relay;
use App\Support\Anchor\AnchorProtocol;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class RelayData extends Data
{
    /** @param array<int, string> $capabilities */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public ?string $publicUrl,
        public ?string $panelUrlOverride,
        /** The override cascade already resolved -- what the relay is actually told to call. */
        public string $panelUrl,
        public int $nodesCount,
        public ?string $enrollmentExpiresAt,
        public ?string $enrolledAt,
        public ?string $lastSeenAt,
        public ?string $version,
        public ?int $protocolMin,
        public ?int $protocolMax,
        public int $panelProtocolVersion,
        public array $capabilities,
        public AnchorCompatibility $compatibility,
    ) {}

    public static function fromModel(Relay $relay): self
    {
        return new self(
            id: $relay->id,
            uuid: $relay->uuid,
            name: $relay->name,
            publicUrl: $relay->public_url,
            panelUrlOverride: $relay->panel_url_override,
            panelUrl: $relay->anchorPanelUrl(),
            nodesCount: (int) ($relay->nodes_count ?? 0),
            enrollmentExpiresAt: $relay->enrollment_expires_at?->toIso8601String(),
            enrolledAt: $relay->enrolled_at?->toIso8601String(),
            lastSeenAt: $relay->last_seen_at?->toIso8601String(),
            version: $relay->version,
            protocolMin: $relay->protocol_min,
            protocolMax: $relay->protocol_max,
            panelProtocolVersion: AnchorProtocol::VERSION,
            capabilities: $relay->capabilities ?? [],
            compatibility: $relay->anchorCompatibility(),
        );
    }
}
