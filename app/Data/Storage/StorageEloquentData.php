<?php

namespace App\Data\Storage;

use App\Data\Node\Storage\StorageData;
use App\Models\Storage;
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
        public bool $isShareable,
        public bool $storesKvm,
        public bool $storesLxc,
        public bool $storesLxcTemplates,
        public bool $storesBackups,
        public bool $storesIso,
        public bool $storesSnippets,
        public ?int $backupOrder,
        // Convoy's own bookkeeping (bytes) — what it has allocated, per resource.
        public int $serverUsage,
        public int $backupUsage,
        public int $isoUsage,
        // Sum of the three above — "Allocated by Convoy".
        public int $committedByConvoy,
        // Live Proxmox figures (bytes). Null when the node/storage is offline or
        // unreachable — the list still renders, flagged not-online.
        public bool $online,
        public ?int $physicalTotal,
        public ?int $physicalUsed,
        public ?int $physicalFree,
        // physicalUsed − committedByConvoy: the base-system + non-Convoy slice,
        // made explicit instead of silently netted out. Null when offline.
        public ?int $untracked,
        // What a new disk may actually consume: physicalFree − reservedBytes.
        // Null when offline (no physical truth to subtract the reserve from).
        public ?int $freeForConvoy,
    ) {}

    public static function fromModel(Storage $storage, ?StorageData $live = null): self
    {
        $serverUsage = (int) ($storage->server_usage ?? 0);
        $backupUsage = (int) ($storage->backup_usage ?? 0);
        $isoUsage = (int) ($storage->iso_usage ?? 0);
        $committed = $serverUsage + $backupUsage + $isoUsage;
        $reserved = (int) ($storage->reserved_bytes ?? 0);

        return new self(
            id: $storage->id,
            displayName: $storage->display_name,
            description: $storage->description,
            name: $storage->name,
            size: (int) $storage->size,
            reservedBytes: $storage->reserved_bytes,
            isShareable: (bool) $storage->is_shareable,
            storesKvm: (bool) $storage->stores_kvm,
            storesLxc: (bool) $storage->stores_lxc,
            storesLxcTemplates: (bool) $storage->stores_lxc_templates,
            storesBackups: (bool) $storage->stores_backups,
            storesIso: (bool) $storage->stores_iso,
            storesSnippets: (bool) $storage->stores_snippets,
            backupOrder: $storage->pivot?->backup_order,
            serverUsage: $serverUsage,
            backupUsage: $backupUsage,
            isoUsage: $isoUsage,
            committedByConvoy: $committed,
            online: $live !== null,
            physicalTotal: $live?->total,
            physicalUsed: $live?->used,
            physicalFree: $live?->free,
            untracked: $live !== null ? max(0, $live->used - $committed) : null,
            freeForConvoy: $live !== null ? max(0, $live->free - $reserved) : null,
        );
    }
}
