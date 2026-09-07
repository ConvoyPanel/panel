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
        public string $name,
        /** The name this ISO takes on every node that fetches it. */
        public string $fileName,
        /** Set when the operator hosts it; null when the panel does. */
        public ?string $url,
        /** True when the panel is serving the file itself. */
        public bool $isHosted,
        public ?string $sha256,
        public ?int $size,
        public bool $hidden,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(ISO $iso): self
    {
        return new self(
            uuid: $iso->uuid,
            name: $iso->name,
            fileName: $iso->file_name,
            url: $iso->url,
            isHosted: $iso->isHosted(),
            sha256: $iso->sha256,
            size: $iso->getRawOriginal('size') !== null ? (int) $iso->size : null,
            hidden: (bool) $iso->hidden,
            createdAt: CarbonImmutable::parse($iso->created_at),
        );
    }
}
