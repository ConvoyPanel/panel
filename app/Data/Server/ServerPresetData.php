<?php

namespace App\Data\Server;

use App\Models\ServerPreset;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ServerPresetData extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $description,
        public ServerPresetSettingsData $settings,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function fromModel(ServerPreset $preset): self
    {
        return new self(
            uuid: $preset->uuid,
            name: $preset->name,
            description: $preset->description,
            settings: ServerPresetSettingsData::from($preset->settings ?? []),
            createdAt: CarbonImmutable::instance($preset->created_at),
            updatedAt: CarbonImmutable::instance($preset->updated_at),
        );
    }
}
