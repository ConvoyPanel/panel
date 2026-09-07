<?php

namespace App\Data\Image;

use App\Models\ImageGroup;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ImageGroupData extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $description,
        public ?string $icon,
        public bool $isAdminOnly,
        #[LoadRelation]
        /** @var Lazy|DataCollection<int, ImageDefinitionData> */
        public Lazy|DataCollection $definitions,
    ) {}

    public static function fromModel(ImageGroup $group): self
    {
        return new self(
            uuid: $group->uuid,
            name: $group->name,
            description: $group->description,
            icon: $group->icon,
            isAdminOnly: (bool) $group->is_admin_only,
            definitions: Lazy::whenLoaded(
                'definitions',
                $group,
                fn () => ImageDefinitionData::collect($group->definitions, DataCollection::class),
            ),
        );
    }
}
