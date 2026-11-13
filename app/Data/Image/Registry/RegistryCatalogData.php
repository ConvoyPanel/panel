<?php

namespace App\Data\Image\Registry;

use Spatie\LaravelData\Data;

/**
 * A published catalogue, as the admin area browses it.
 *
 * Not a stored entity: this is the decoded result of one fetch, cached for
 * minutes and thrown away. Nothing in the panel subscribes to a catalogue, and
 * an import copies an entry rather than linking to one.
 */
class RegistryCatalogData extends Data
{
    public function __construct(
        public string $url,
        public string $name,
        public ?string $description,
        public ?string $generatedAt,
        /**
         * A plain array rather than a DataCollection: the root of this
         * response is wrapped in `data`, and a DataCollection one level down
         * would be wrapped again while `templates` below it would not -- two
         * shapes in one document for no reason the client can act on.
         *
         * @var array<int, RegistryGroupData>
         */
        public array $groups,
    ) {}
}
