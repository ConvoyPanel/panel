<?php

namespace App\Data\User;

use App\Models\Passkey;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class PasskeyData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?CarbonImmutable $lastUsedAt,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(Passkey $passkey): self
    {
        return new self(
            id: $passkey->id,
            name: $passkey->name,
            lastUsedAt: $passkey->last_used_at
                ? CarbonImmutable::parse($passkey->last_used_at)
                : null,
            createdAt: CarbonImmutable::parse($passkey->created_at),
        );
    }
}
