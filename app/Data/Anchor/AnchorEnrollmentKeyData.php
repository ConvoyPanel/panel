<?php

namespace App\Data\Anchor;

use App\Data\User\UserData;
use App\Enums\Anchor\AnchorMode;
use App\Enums\Anchor\EnrollmentKeyStatus;
use App\Models\Anchor;
use App\Models\AnchorEnrollmentKey;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapInputName(SnakeCaseMapper::class)]
class AnchorEnrollmentKeyData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        /** Null means the key admits either an agent or a relay. */
        public ?AnchorMode $mode,
        public ?int $maxUses,
        public int $uses,
        public EnrollmentKeyStatus $status,
        public ?string $expiresAt,
        public ?string $revokedAt,
        public ?string $lastUsedAt,
        /**
         * Present only in the response to creating the key. There is no second
         * chance to read it: only the SHA-256 is stored, so a lost token is
         * replaced rather than recovered -- the same contract as a panel-wide
         * API token.
         */
        public Optional|string $token,
        /** The install command, built around the token, and equally single-shot. */
        public Optional|string $command,
        /** Null once that admin is deleted; the key outlives them. */
        #[LoadRelation]
        public Lazy|UserData|null $createdBy,
    ) {}

    public static function fromModel(AnchorEnrollmentKey $key, ?string $token = null): self
    {
        return new self(
            id: $key->id,
            uuid: $key->uuid,
            name: $key->name,
            mode: $key->mode,
            maxUses: $key->max_uses,
            uses: $key->uses,
            status: $key->status(),
            expiresAt: $key->expires_at?->toIso8601String(),
            revokedAt: $key->revoked_at?->toIso8601String(),
            lastUsedAt: $key->last_used_at?->toIso8601String(),
            token: $token ?? Optional::create(),
            command: $token === null
                ? Optional::create()
                : sprintf(
                    "anchor enroll --panel-url %s --token '%s'",
                    // No row exists yet to carry a per-Anchor override, so the
                    // command can only name the panel-wide address.
                    Anchor::defaultPanelUrl(),
                    $token,
                ),
            createdBy: Lazy::whenLoaded(
                'createdBy',
                $key,
                fn () => $key->createdBy ? UserData::from($key->createdBy) : null,
            ),
        );
    }
}
