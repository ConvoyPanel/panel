<?php

namespace App\Data\Storage;

use App\Models\Storage;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class StorageEloquentData extends Data
{
    public function __construct(
        public int $id,
        public ?string $displayName,
        public ?string $description,
        public string $name,
        public int $size,
        public bool $isShareable,
        public bool $storesKvm,
        public bool $storesLxc,
        public bool $storesLxcTemplates,
        public bool $storesBackups,
        public bool $storesIso,
        public bool $storesSnippets,
        public ?int $backupOrder,
        public int $serverUsage,
        public int $backupUsage,
        public int $isoUsage,
    ) {}

    public static function fromModel(Storage $storage): self
    {
        return new self(
            id: $storage->id,
            displayName: $storage->display_name,
            description: $storage->description,
            name: $storage->name,
            size: (int) $storage->size,
            isShareable: (bool) $storage->is_shareable,
            storesKvm: (bool) $storage->stores_kvm,
            storesLxc: (bool) $storage->stores_lxc,
            storesLxcTemplates: (bool) $storage->stores_lxc_templates,
            storesBackups: (bool) $storage->stores_backups,
            storesIso: (bool) $storage->stores_iso,
            storesSnippets: (bool) $storage->stores_snippets,
            backupOrder: $storage->pivot?->backup_order,
            serverUsage: (int) ($storage->server_usage ?? 0),
            backupUsage: (int) ($storage->backup_usage ?? 0),
            isoUsage: (int) ($storage->iso_usage ?? 0),
        );
    }
}
