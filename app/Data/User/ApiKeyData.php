<?php

namespace App\Data\User;

use App\Models\PersonalAccessToken;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapInputName(SnakeCaseMapper::class)]
class ApiKeyData extends Data
{
    public function __construct(
        public int $id,
        public ?string $type,
        public string $name,
        public ?CarbonImmutable $lastUsedAt,
        public Optional|string $plainTextToken,
        #[LoadRelation]
        public Lazy|UserData $user,
    ) {}

    public static function fromModel(PersonalAccessToken $token, ?string $plainTextToken = null): self
    {
        return new self(
            id: $token->id,
            type: $token->type,
            name: $token->name,
            lastUsedAt: $token->last_used_at
                ? CarbonImmutable::parse($token->last_used_at)
                : null,
            plainTextToken: $plainTextToken ?? Optional::create(),
            user: Lazy::whenLoaded(
                'tokenable',
                $token,
                fn () => UserData::from($token->tokenable),
            ),
        );
    }
}
