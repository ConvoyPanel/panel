<?php

namespace App\Data\Image\Registry;

use Spatie\LaravelData\Data;

/**
 * What one import did, which is either a new version or nothing.
 *
 * `created` false is the ordinary outcome of importing something twice, not a
 * failure: the panel already holds a version with exactly these disks, so there
 * is nothing to add.
 */
class RegistryImportResultData extends Data
{
    public function __construct(
        public string $slug,
        public string $imageGroupUuid,
        public string $imageDefinitionUuid,
        public string $version,
        public bool $created,
    ) {}
}
