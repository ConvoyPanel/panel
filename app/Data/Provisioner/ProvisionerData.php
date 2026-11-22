<?php

namespace App\Data\Provisioner;

use App\Models\Provisioner;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ProvisionerData extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $description,
        public string $script,
        /** @var DataCollection<int, ProvisionerFieldData> */
        public DataCollection $fields,
        /** How many images run it. */
        public int $imageCount,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function fromModel(Provisioner $provisioner): self
    {
        return new self(
            uuid: $provisioner->uuid,
            name: $provisioner->name,
            description: $provisioner->description,
            script: $provisioner->script,
            // Unwrapped, so a single provisioner and a list of them agree on
            // the shape (see StorageConsumersData).
            fields: ProvisionerFieldData::collect($provisioner->fieldDefinitions(), DataCollection::class)
                ->withoutWrapping(),
            imageCount: (int) ($provisioner->images_count ?? $provisioner->images()->count()),
            createdAt: CarbonImmutable::instance($provisioner->created_at),
            updatedAt: CarbonImmutable::instance($provisioner->updated_at),
        );
    }
}
