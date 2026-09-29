<?php

namespace App\Data\Server\Proxmox\Migration;

use Illuminate\Support\Arr;
use Spatie\LaravelData\Data;

/**
 * Why PVE will not send this guest to one particular member.
 */
class NodeMigrationBlockerData extends Data
{
    public function __construct(
        /** @var array<int, string> storage ids the destination does not have */
        public readonly array $unavailableStorages,
        /** @var array<int, string> HA resource ids ("<type>:<id>") holding it back */
        public readonly array $blockingHaResources,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            unavailableStorages: array_values(Arr::get($raw, 'unavailable_storages') ?: []),
            blockingHaResources: collect(Arr::get($raw, 'blocking-ha-resources') ?: [])
                ->map(fn (array $resource) => (string) Arr::get($resource, 'sid'))
                ->all(),
        );
    }

    /** One line an operator can act on, or null when PVE gave no detail. */
    public function summary(): ?string
    {
        if ($this->unavailableStorages !== []) {
            return 'Storage '.implode(', ', $this->unavailableStorages).' is not available there.';
        }

        if ($this->blockingHaResources !== []) {
            return 'Held by HA rules on '.implode(', ', $this->blockingHaResources).'.';
        }

        return null;
    }
}
