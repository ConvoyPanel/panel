<?php

namespace App\Data\Image\Registry;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class RegistryGroupData extends Data
{
    public function __construct(
        public string $slug,
        public string $name,
        public ?string $description,
        /** @var DataCollection<int, RegistryTemplateData> */
        public DataCollection $templates,
    ) {}
}
