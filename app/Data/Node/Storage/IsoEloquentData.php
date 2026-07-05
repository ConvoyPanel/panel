<?php

namespace App\Data\Node\Storage;

use App\Models\ISO;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class IsoEloquentData extends Data
{
    public function __construct(
        public string $uuid,
        public bool $isSuccessful,
        public string $name,
        public ?string $fileName,
        public ?int $size,
        public bool $hidden,
        public ?CarbonImmutable $completedAt,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(ISO $iso): self
    {
        return new self(
            uuid: $iso->uuid,
            isSuccessful: (bool) $iso->is_successful,
            name: $iso->name,
            fileName: $iso->file_name,
            size: $iso->getRawOriginal('size') !== null ? (int) $iso->size : null,
            hidden: (bool) $iso->hidden,
            completedAt: $iso->completed_at
                ? CarbonImmutable::parse($iso->completed_at)
                : null,
            createdAt: CarbonImmutable::parse($iso->created_at),
        );
    }
}
