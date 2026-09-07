<?php

namespace App\Data\Image;

use App\Models\ImageDefinition;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ImageDefinitionData extends Data
{
    public function __construct(
        public string $uuid,
        public string $imageGroupUuid,
        public string $name,
        public ?string $description,
        public bool $isAdminOnly,
        public string $ostype,
        /**
         * What the admin has actually overridden. Sent alongside the effective
         * map so the form can show which fields are inherited and which are
         * theirs -- clearing one has to mean "inherit again", not "set empty".
         */
        public array $hardware,
        /** The overlay resolved over the OS default: what `qm create` receives. */
        public array $effectiveHardware,
        public ?int $minimumCores,
        public ?int $minimumMemory,
        /** The newest active version, or null when none has been added yet. */
        public ?ImageVersionData $latestVersion,
        #[LoadRelation]
        /** @var Lazy|DataCollection<int, ImageVersionData> */
        public Lazy|DataCollection $versions,
    ) {}

    public static function fromModel(ImageDefinition $definition): self
    {
        $latest = $definition->relationLoaded('versions')
            ? $definition->versions->where('is_active', true)->sortByDesc(
                fn ($v) => [$v->version_major, $v->version_minor, $v->version_patch],
            )->first()
            : $definition->latestVersion();

        return new self(
            uuid: $definition->uuid,
            imageGroupUuid: $definition->group->uuid,
            name: $definition->name,
            description: $definition->description,
            isAdminOnly: (bool) $definition->is_admin_only,
            ostype: $definition->ostype,
            hardware: $definition->hardware ?? [],
            effectiveHardware: $definition->effectiveHardware(),
            minimumCores: $definition->minimum_cores,
            minimumMemory: $definition->minimum_memory,
            latestVersion: $latest ? ImageVersionData::fromModel($latest) : null,
            versions: Lazy::whenLoaded(
                'versions',
                $definition,
                fn () => ImageVersionData::collect($definition->versions, DataCollection::class),
            ),
        );
    }
}
