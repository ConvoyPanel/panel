<?php

namespace App\Data\User;

use App\Models\OAuthConnection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class OAuthConnectionData extends Data
{
    public function __construct(
        public int $id,
        public string $provider,
        // Human-readable provider name from config/oauth.php, so the UI needn't hardcode a map.
        public string $label,
        public ?string $name,
        public ?string $email,
        public ?CarbonImmutable $lastUsedAt,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(OAuthConnection $connection): self
    {
        return new self(
            id: $connection->id,
            provider: $connection->provider,
            label: (string) config("oauth.providers.{$connection->provider}.label", Str::title($connection->provider)),
            name: $connection->name,
            email: $connection->email,
            lastUsedAt: $connection->last_used_at ? CarbonImmutable::parse($connection->last_used_at) : null,
            createdAt: CarbonImmutable::parse($connection->created_at),
        );
    }
}
