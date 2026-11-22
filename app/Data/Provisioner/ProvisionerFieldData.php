<?php

namespace App\Data\Provisioner;

use Spatie\LaravelData\Data;

/**
 * One value a provisioner asks for when a server is built from its image.
 *
 * The script reads it as `CONVOY_FIELD_<KEY>`.
 */
class ProvisionerFieldData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $required,
    ) {}

    public function environmentName(): string
    {
        return 'CONVOY_FIELD_'.strtoupper($this->key);
    }
}
