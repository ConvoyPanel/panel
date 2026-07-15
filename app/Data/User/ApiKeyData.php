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
        /** @var list<string> */
        public array $abilities,
        /** @var list<string> */
        public array $allowedNetworks,
        public ?CarbonImmutable $lastUsedAt,
        public Optional|string $plainTextToken,
        // The admin who minted the token (audit). Null once that admin is deleted — the token lives on.
        #[LoadRelation]
        public Lazy|UserData|null $createdBy,
    ) {}

    public static function fromModel(PersonalAccessToken $token, ?string $plainTextToken = null): self
    {
        return new self(
            id: $token->id,
            type: $token->type->value,
            name: $token->name,
            abilities: $token->abilities ?? ['*'],
            allowedNetworks: $token->allowed_networks ?? [],
            lastUsedAt: $token->last_used_at
                ? CarbonImmutable::parse($token->last_used_at)
                : null,
            plainTextToken: $plainTextToken ?? Optional::create(),
            createdBy: Lazy::whenLoaded(
                'createdBy',
                $token,
                fn () => $token->createdBy
                    ? UserData::from($token->createdBy)
                    : null,
            ),
        );
    }
}
