<?php

namespace App\Data\Anchor;

use App\Enums\Anchor\AnchorCompatibility;
use App\Enums\Anchor\AnchorMode;
use App\Models\AnchorEnrollment;
use App\Support\Anchor\AnchorProtocol;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A machine waiting to be let in, plus what it said about itself and what the
 * panel would fill the approval form with.
 */
#[MapInputName(SnakeCaseMapper::class)]
class AnchorEnrollmentQueueData extends Data
{
    /**
     * @param  array<string, mixed>|null  $reportedFacts
     * @param  array<string, mixed>  $suggestions
     * @param  array<int, string>  $capabilities
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public AnchorMode $mode,
        public ?string $enrollmentKeyName,
        public ?string $enrolledAt,
        public ?string $lastSeenAt,
        public ?string $version,
        public ?int $protocolMin,
        public ?int $protocolMax,
        public int $panelProtocolVersion,
        public array $capabilities,
        public AnchorCompatibility $compatibility,
        public ?array $reportedFacts,
        /**
         * Everything the machine already answered, shaped as node fields. The
         * approval screen is a confirmation of what was found, not a blank form.
         */
        public array $suggestions,
    ) {}

    /** @param array<string, mixed> $suggestions */
    public static function fromModel(AnchorEnrollment $enrollment, array $suggestions = []): self
    {
        return new self(
            id: $enrollment->id,
            uuid: $enrollment->uuid,
            name: $enrollment->name,
            mode: $enrollment->mode,
            enrollmentKeyName: $enrollment->enrollmentKey?->name,
            enrolledAt: $enrollment->enrolled_at?->toIso8601String(),
            lastSeenAt: $enrollment->last_seen_at?->toIso8601String(),
            version: $enrollment->version,
            protocolMin: $enrollment->protocol_min,
            protocolMax: $enrollment->protocol_max,
            panelProtocolVersion: AnchorProtocol::VERSION,
            capabilities: $enrollment->capabilities ?? [],
            compatibility: $enrollment->anchorCompatibility(),
            reportedFacts: $enrollment->reported_facts,
            suggestions: $suggestions,
        );
    }
}
