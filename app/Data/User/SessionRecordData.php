<?php

namespace App\Data\User;

use App\Models\SessionRecord;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class SessionRecordData extends Data
{
    public function __construct(
        public int $id,
        public ?string $ipAddress,
        public ?string $userAgent,
        public CarbonImmutable $lastActiveAt,
        // Whether this row is the session making the request (can't be silently revoked out from
        // under the user, and gets a "This device" marker in the UI).
        public bool $isCurrent,
    ) {}

    public static function fromModel(SessionRecord $record, string $currentSessionId): self
    {
        return new self(
            id: $record->id,
            ipAddress: $record->ip_address,
            userAgent: $record->user_agent,
            lastActiveAt: CarbonImmutable::parse($record->last_active_at),
            isCurrent: $record->session_id === $currentSessionId,
        );
    }
}
