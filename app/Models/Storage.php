<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use App\Support\ByteUnit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * One `storage.cfg` definition, scoped to its cluster (or to a standalone
 * node's singleton scope) and unique by name within it. Which nodes actually
 * mount it -- and how full each mount is -- lives on the `storage_to_node`
 * links, so one shared pool is one row with N links, and one local definition
 * instantiated on N nodes is *also* one row with N links, each link carrying
 * that node's own figures.
 *
 * @property int $id
 * @property ?int $cluster_id
 * @property ?string $display_name
 * @property ?string $description
 * @property string $name
 * @property int $size
 * @property ?int $reserved_bytes
 * @property ?string $pve_type
 * @property ?bool $pve_shared
 * @property ?string $pve_content
 * @property bool $stores_kvm
 * @property bool $stores_lxc
 * @property bool $stores_lxc_templates
 * @property bool $stores_backups
 * @property bool $stores_iso
 * @property bool $stores_snippets
 * @property int $server_usage
 * @property int $backup_usage
 * @property int $iso_usage
 * @property ?StorageToNode $pivot
 * @property ?Cluster $cluster
 */
class Storage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [
        'id',
    ];

    public static array $validationRules = [
        'display_name' => 'nullable|string|max:40',
        'description' => 'nullable|string|max:191',
        'name' => 'required|string|max:191',
        'size' => 'required|numeric|min:1',
        'reserved_bytes' => 'nullable|numeric|min:0',
        // No `stores_*` rules: the content flags are never submitted. They are
        // read off Proxmox at registration and restated by every poll, so
        // accepting a client's version of them would only let it disagree with
        // the host.
    ];

    protected function casts(): array
    {
        return [
            'size' => StorageSizeCast::class,
            'reserved_bytes' => StorageSizeCast::class,
            'pve_shared' => 'boolean',
            'stores_kvm' => 'boolean',
            'stores_lxc' => 'boolean',
            'stores_lxc_templates' => 'boolean',
            'stores_backups' => 'boolean',
            'stores_iso' => 'boolean',
            'stores_snippets' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Node, $this, StorageToNode>
     */
    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(
            Node::class,
            'storage_to_node',
            'storage_id',
            'node_id',
        )
            ->using(StorageToNode::class)
            ->withPivot('backup_order', 'discovered_total', 'discovered_used', 'discovered_at');
    }

    /**
     * @return BelongsTo<Cluster, $this>
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    /**
     * The last observed capacity, resolved per placement.
     *
     * The figures live on the (storage, node) links, so "how full is it" needs
     * a node in scope. Given one, the answer is that link's own reading. Fleet
     * wide it depends on the backend: a shared pool is one pool however many
     * nodes read it, so the freshest reading stands for all of them; a local
     * definition names a different disk on every node, so the readings sum --
     * and the sum is only as fresh as its stalest part, which is why `at`
     * takes the oldest timestamp there.
     *
     * @return array{total: ?int, used: ?int, at: ?CarbonImmutable}
     */
    public function recordedCapacity(?Node $node = null): array
    {
        $observed = $this->observedLinks()
            ->filter(fn (StorageToNode $link) => $link->discovered_at !== null);

        if ($node !== null) {
            $observed = $observed->where('node_id', $node->id);
        }

        if ($observed->isEmpty()) {
            return ['total' => null, 'used' => null, 'at' => null];
        }

        if ($node !== null || $this->pve_shared) {
            /** @var StorageToNode $freshest */
            $freshest = $observed->sortByDesc('discovered_at')->first();

            return [
                'total' => (int) $freshest->discovered_total,
                'used' => (int) $freshest->discovered_used,
                'at' => $freshest->discovered_at,
            ];
        }

        return [
            'total' => (int) $observed->sum('discovered_total'),
            'used' => (int) $observed->sum('discovered_used'),
            'at' => $observed->min('discovered_at'),
        ];
    }

    /**
     * This storage's links, from the loaded relation when the caller eager
     * loaded it (one query for a whole listing) and from a query when not.
     *
     * @return Collection<int, StorageToNode>
     */
    private function observedLinks(): Collection
    {
        if ($this->relationLoaded('nodes')) {
            return $this->nodes->map(fn (Node $node) => $node->pivot)->values();
        }

        return StorageToNode::query()->where('storage_id', $this->id)->get()->toBase();
    }

    /**
     * Get the ISO images stored on this storage.
     */
    public function isos(): HasMany
    {
        // Assumes 'storage_id' foreign key exists on the 'iso_library' table
        return $this->hasMany(ISO::class);
    }

    /**
     * Get the servers whose primary disk resides on this storage.
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Get the VM disks (primary and secondary) that reside on this storage.
     * This is the disk-oriented source for "Allocated by Convoy" — a server
     * can have disks on several storages, so we sum disk rows, not servers.
     */
    public function serverDisks(): HasMany
    {
        return $this->hasMany(ServerDisk::class);
    }

    /**
     * Get the backups stored on this storage.
     */
    public function backups(): HasMany
    {
        // Assumes 'storage_id' foreign key exists on the 'backups' table
        return $this->hasMany(Backup::class);
    }

    /**
     * Query Scope to automatically include the sums of related storage usage.
     *
     * Call this like: Storage::withUsageSums()->find(1);
     *
     * @param  Builder  $query  The Eloquent query builder.
     */
    public function scopeWithUsageSums(Builder $query): void
    {
        $query->withSum('serverDisks as servers_sum_disk', 'size')
            ->withSum('backups as backups_sum_size', 'size')
            ->withSum('isos as isos_sum_size', 'size');
    }

    /**
     * Helper method to get usage value, checking for pre-loaded sums first.
     *
     * @param  string  $relationshipName  The name of the relationship method (e.g., 'servers').
     * @param  string  $sumColumn  The column to sum on the related table (e.g., 'disk').
     * @param  string  $preloadedSumAttribute  The expected attribute name if loaded via withSum (e.g., 'servers_sum_disk').
     */
    private function getUsageAttributeValue(string $relationshipName, string $sumColumn, string $preloadedSumAttribute): int
    {
        // Check if a sum was loaded via withSum() using the expected attribute name
        if (array_key_exists($preloadedSumAttribute, $this->attributes)) {
            // Return the preloaded value, defaulting to 0 if null
            return ByteUnit::Mebibytes->toBytes((int) ($this->attributes[$preloadedSumAttribute] ?? 0)); // convert from MiB to bytes
        }

        // Fallback: Calculate on the fly using the relationship method
        // Warning: Can cause N+1 query issues if withSum wasn't used on collections
        // Use Str::camel to call the relationship method dynamically (e.g., 'servers' -> $this->servers())
        $relationshipMethod = Str::camel($relationshipName);
        if (method_exists($this, $relationshipMethod)) {
            return ByteUnit::Mebibytes->toBytes((int) ($this->$relationshipMethod()->sum($sumColumn) ?? 0)); // convert from MiB to bytes
        }

        // Return 0 if the relationship method doesn't exist (should not happen with the correct usage)
        return 0;
    }

    /**
     * Accessor for server disk usage.
     */
    public function getServerUsageAttribute(): int
    {
        return $this->getUsageAttributeValue('serverDisks', 'size', 'servers_sum_disk');
    }

    /**
     * Accessor for backup size usage.
     */
    public function getBackupUsageAttribute(): int
    {
        return $this->getUsageAttributeValue('backups', 'size', 'backups_sum_size');
    }

    /**
     * Accessor for ISO size usage.
     */
    public function getIsoUsageAttribute(): int
    {
        return $this->getUsageAttributeValue('isos', 'size', 'isos_sum_size');
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
