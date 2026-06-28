<?php

namespace App\Data\Template;

use App\Data\Server\Templates\TemplateData;
use App\Models\TemplateGroup;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class TemplateGroupData extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $description,
        public ?string $icon,
        public bool $isAdminOnly,
        #[LoadRelation]
        /** @var Lazy|DataCollection<int, TemplateData> */
        public Lazy|DataCollection $templates,
    ) {}

    public static function fromModel(TemplateGroup $group): self
    {
        return new self(
            uuid: $group->uuid,
            name: $group->name,
            description: $group->description,
            icon: $group->icon,
            isAdminOnly: (bool) $group->is_admin_only,
            templates: Lazy::whenLoaded(
                'templates',
                $group,
                fn () => TemplateData::collect($group->templates, DataCollection::class),
            ),
        );
    }
}
