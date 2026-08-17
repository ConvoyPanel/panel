<?php

namespace App\Data\Storage;

use App\Data\Node\Storage\StorageData;
use App\Models\Node;
use App\Models\Storage;
use App\Support\StorageBackends;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

use function max;

#[MapInputName(SnakeCaseMapper::class)]
class StorageEloquentData extends Data
{
    public function __construct(
        public int $id,
        public ?string $displayName,
        public ?string $description,
        public string $name,
        public int $size,
        public ?int $reservedBytes,
        public bool $storesKvm,
        public bool $storesLxc,
        public bool $storesLxcTemplates,
        public bool $storesBackups,
        public bool $storesIso,
        public bool $storesSnippets,
        public ?int $backupOrder,
        /**
         * The other nodes reaching this same storage, named.
         *
         * A shared pool's capacity is not this node's alone, and a badge saying
         * "shared" does not convey that -- an operator reading 20 TiB of free
         * Ceph on four nodes will happily plan four nodes' growth against one
         * disk. Naming the others is what stops that.
         *
         * @var array<int, array{id: int, name: string}>
         */
        public array $sharedWith,
        // What Proxmox says this storage is, recorded by the poll. Null until a
        // node has reported it at least once.
        public ?string $pveType,
        public ?bool $pveShared,
        public ?string $pveContent,
        /**
         * Whether committed may legitimately exceed physical usage — thin
         * backends and PBS. The UI needs this to know that a large gap is
         * ordinary rather than something to warn about.
         */
        public bool $isThin,
        // Convoy's own bookkeeping (bytes) — what it has allocated, per resource.
        public int $serverUsage,
        public int $backupUsage,
        public int $isoUsage,
        // Sum of the three above — "Allocated by Convoy".
        public int $committedByConvoy,
        // Whether the figures below came from a live call this request.
        public bool $online,
        /**
         * Where the physical figures came from: `live` this request, `recorded`
         * by the last poll, or `unknown` if no node has ever reported it.
         *
         * The page used to go blank the moment a node was unreachable, because
         * live was the only source. The poll now writes the same figures, so a
         * brief outage costs freshness rather than the whole panel — but only if
         * the UI can say which it is showing.
         */
        public string $capacitySource,
        /** When the physical figures were observed. Null when never. */
        public ?CarbonImmutable $observedAt,
        public ?int $physicalTotal,
        public ?int $physicalUsed,
        public ?int $physicalFree,
        /**
         * physicalUsed − committedByConvoy: the slice Convoy cannot account for.
         *
         * Null when there is nothing to subtract from, and null on thin or
         * deduplicating backends where the subtraction is not valid — there the
         * ledger legitimately exceeds physical bytes, and clamping the result at
         * zero would present "no unaccounted space" as a finding rather than an
         * artefact of the arithmetic.
         */
        public ?int $untracked,
        // What a new disk may actually consume: physicalFree − reservedBytes.
        public ?int $freeForConvoy,
    ) {}

    public static function fromModel(
        Storage $storage,
        ?StorageData $live = null,
        ?Node $viewedFrom = null,
    ): self {
        $serverUsage = (int) ($storage->server_usage ?? 0);
        $backupUsage = (int) ($storage->backup_usage ?? 0);
        $isoUsage = (int) ($storage->iso_usage ?? 0);
        $committed = $serverUsage + $backupUsage + $isoUsage;
        $reserved = (int) ($storage->reserved_bytes ?? 0);
        $isThin = StorageBackends::isThin($storage->pve_type);

        // Live if we have it, otherwise whatever the poll last wrote. `free` is
        // derived rather than stored: PVE gives used and total on the cluster
        // rows, and total − used is the same number it would have reported.
        [$source, $observedAt, $total, $used] = match (true) {
            $live !== null => ['live', CarbonImmutable::now(), $live->total, $live->used],
            $storage->discovered_at !== null => [
                'recorded',
                $storage->discovered_at,
                (int) $storage->discovered_total,
                (int) $storage->discovered_used,
            ],
            default => ['unknown', null, null, null],
        };

        $free = $total !== null ? max(0, $total - $used) : null;

        return new self(
            id: $storage->id,
            displayName: $storage->display_name,
            description: $storage->description,
            name: $storage->name,
            size: (int) $storage->size,
            reservedBytes: $storage->reserved_bytes,
            storesKvm: (bool) $storage->stores_kvm,
            storesLxc: (bool) $storage->stores_lxc,
            storesLxcTemplates: (bool) $storage->stores_lxc_templates,
            storesBackups: (bool) $storage->stores_backups,
            storesIso: (bool) $storage->stores_iso,
            storesSnippets: (bool) $storage->stores_snippets,
            backupOrder: $storage->pivot?->backup_order,
            sharedWith: $storage->relationLoaded('nodes') || $viewedFrom !== null
                ? $storage->nodes
                    ->reject(fn (Node $node) => $viewedFrom !== null && $node->is($viewedFrom))
                    // Carries the id as well as the name so a list with no node
                    // in scope can link to each one -- a fleet page you cannot
                    // navigate from is a dead end.
                    ->map(fn (Node $node) => [
                        'id' => $node->id,
                        'name' => $node->display_name ?? $node->name,
                    ])
                    ->values()
                    ->all()
                : [],
            pveType: $storage->pve_type,
            pveShared: $storage->pve_shared,
            pveContent: $storage->pve_content,
            isThin: $isThin,
            serverUsage: $serverUsage,
            backupUsage: $backupUsage,
            isoUsage: $isoUsage,
            committedByConvoy: $committed,
            online: $live !== null,
            capacitySource: $source,
            observedAt: $observedAt,
            physicalTotal: $total,
            physicalUsed: $used,
            // Live reports free directly; a recorded figure derives it. Prefer
            // the reported one, which accounts for filesystem overhead the
            // subtraction cannot see.
            physicalFree: $live?->free ?? $free,
            untracked: $used !== null && ! $isThin ? max(0, $used - $committed) : null,
            freeForConvoy: ($live?->free ?? $free) !== null
                ? max(0, ($live?->free ?? $free) - $reserved)
                : null,
        );
    }
}
