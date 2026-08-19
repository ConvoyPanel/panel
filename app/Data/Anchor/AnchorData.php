<?php

namespace App\Data\Anchor;

use App\Enums\Anchor\AnchorCompatibility;
use App\Enums\Anchor\AnchorMode;
use App\Models\Anchor;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class AnchorData extends Data
{
    /**
     * @param  array<int, string>  $capabilities
     * @param  DataCollection<int, AnchorNodeData>|null  $nodes
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public AnchorMode $mode,
        public string $publicUrl,
        public ?string $panelUrlOverride,
        /** The override cascade already resolved -- what the anchor is actually told to call. */
        public string $panelUrl,
        public ?int $relayId,
        public ?string $relayName,
        public int $nodesCount,
        public int $agentsCount,
        public ?string $enrollmentExpiresAt,
        public ?string $enrolledAt,
        public ?string $lastSeenAt,
        public ?string $version,
        public ?int $protocolMin,
        public ?int $protocolMax,
        public int $panelProtocolVersion,
        public array $capabilities,
        public AnchorCompatibility $compatibility,
        /**
         * Only the detail endpoint loads these; on the index the roster shows
         * `nodesCount` and nothing more, and eager-loading every anchor's nodes
         * to render a number would be a query per row for data nobody reads.
         * Null therefore means "not asked for", not "none attached".
         */
        public ?DataCollection $nodes = null,
    ) {}

    public static function fromModel(Anchor $anchor): self
    {
        return new self(
            id: $anchor->id,
            uuid: $anchor->uuid,
            name: $anchor->name,
            mode: $anchor->mode,
            publicUrl: $anchor->public_url,
            panelUrlOverride: $anchor->panel_url_override,
            panelUrl: $anchor->panelUrl(),
            relayId: $anchor->relay_id,
            // Eager-loaded by every endpoint that builds this DTO: the roster
            // names the relay an agent routes through on each row, and
            // resolving it client-side is not safe across a paginated list.
            relayName: $anchor->relay?->name,
            nodesCount: (int) ($anchor->nodes_count ?? 0),
            agentsCount: (int) ($anchor->agents_count ?? 0),
            // An outstanding install command. Consuming the enrollment clears
            // it (see EnrollmentController), so this is only ever set while a
            // token is still live -- which is exactly when the UI has a
            // countdown to show.
            enrollmentExpiresAt: $anchor->enrollment_expires_at?->toIso8601String(),
            enrolledAt: $anchor->enrolled_at?->toIso8601String(),
            lastSeenAt: $anchor->last_seen_at?->toIso8601String(),
            version: $anchor->version,
            protocolMin: $anchor->protocol_min,
            protocolMax: $anchor->protocol_max,
            // Ships with the anchor so "speaks 2-3, panel speaks 1" doesn't
            // hardcode the panel's half of the comparison in TypeScript.
            panelProtocolVersion: Anchor::PROTOCOL_VERSION,
            capabilities: $anchor->capabilities ?? [],
            compatibility: $anchor->compatibility(),
            // `withoutWrapping()` for the same reason every other nested
            // collection here has it (see OverviewService): the global `data`
            // wrapper applies to nested collections too, so without it the
            // client receives an object where it asked for a list.
            nodes: $anchor->relationLoaded('nodes')
                ? AnchorNodeData::collect($anchor->nodes, DataCollection::class)
                    ->withoutWrapping()
                : null,
        );
    }
}
