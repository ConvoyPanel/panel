<?php

namespace App\Data\User;

use App\Models\SSHKey;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class SSHKeyData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $publicKey,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(SSHKey $key): self
    {
        return new self(
            id: $key->id,
            name: $key->name,
            publicKey: $key->public_key,
            createdAt: CarbonImmutable::parse($key->created_at),
        );
    }
}
