<?php

namespace App\Data\Server\Proxmox\Migration;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * What the source node says about moving this guest, from
 * `GET /nodes/{node}/qemu/{vmid}/migrate`.
 *
 * PVE already knows which members have the guest's storages, whether a
 * passed-through device pins it in place, and which HA rules block it, so the
 * panel asks rather than re-deriving. Everything here is PVE's verdict;
 * Convoy's own address verdict is computed separately and layered on top.
 */
class MigrationPreconditionData extends Data
{
    public function __construct(
        /** @var array<int, string> node names PVE would accept */
        public readonly array $allowedNodes,
        /** @var array<string, NodeMigrationBlockerData> keyed by node name */
        public readonly array $notAllowedNodes,
        /** @var array<int, string> passed-through devices that pin the guest in place */
        public readonly array $localResources,
        /** @var array<int, LocalDiskData> */
        public readonly array $localDisks,
        public readonly bool $isRunning,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $notAllowed = Arr::get($raw, 'not_allowed_nodes') ?: [];

        return new self(
            allowedNodes: array_values(Arr::get($raw, 'allowed_nodes') ?: []),
            notAllowedNodes: collect($notAllowed)
                ->map(fn (array $reason) => NodeMigrationBlockerData::fromRaw($reason))
                ->all(),
            localResources: array_values(Arr::get($raw, 'local_resources') ?: []),
            localDisks: collect(Arr::get($raw, 'local_disks') ?: [])
                ->map(fn (array $disk) => LocalDiskData::fromRaw($disk))
                ->all(),
            isRunning: (bool) Arr::get($raw, 'running', false),
        );
    }

    /**
     * Nodes PVE refuses outright, keyed by name. `allowed_nodes` is optional in
     * the API and absent on a single-member cluster, so membership of
     * `not_allowed_nodes` is the reliable half of the pair.
     */
    public function blockerFor(string $node): ?NodeMigrationBlockerData
    {
        return $this->notAllowedNodes[$node] ?? null;
    }

    /**
     * @return Collection<int, string>
     */
    public function allowed(): Collection
    {
        return collect($this->allowedNodes);
    }
}
