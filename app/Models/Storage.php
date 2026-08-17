<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use App\Support\ByteUnit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property ?string $display_name
 * @property ?string $description
 * @property string $name
 * @property int $size
 * @property ?int $reserved_bytes
 * @property ?string $pve_type
 * @property ?bool $pve_shared
 * @property ?string $pve_content
 * @property ?int $discovered_total
 * @property ?int $discovered_used
 * @property ?CarbonImmutable $discovered_at
 * @property bool $is_shareable
 * @property bool $stores_kvm
 * @property bool $stores_lxc
 * @property bool $stores_lxc_templates
 * @property bool $stores_backups
 * @property bool $stores_iso
 * @property bool $stores_snippets
 * @property int $server_usage
 * @property int $backup_usage
 * @property int $iso_usage
 * @property object{backup_order?: int|null}|null $pivot
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
        'is_shareable' => 'required|boolean',
        'stores_kvm' => 'required|boolean',
        'stores_lxc' => 'required|boolean',
        'stores_lxc_templates' => 'required|boolean',
        'stores_backups' => 'required|boolean',
        'stores_iso' => 'required|boolean',
        'stores_snippets' => 'required|boolean',
    ];

    protected function casts(): array
    {
        return [
            'size' => StorageSizeCast::class,
            'reserved_bytes' => StorageSizeCast::class,
            'is_shareable' => 'boolean',
            'pve_shared' => 'boolean',
            'discovered_at' => 'immutable_datetime',
            'stores_kvm' => 'boolean',
            'stores_lxc' => 'boolean',
            'stores_lxc_templates' => 'boolean',
            'stores_backups' => 'boolean',
            'stores_iso' => 'boolean',
            'stores_snippets' => 'boolean',
        ];
    }

    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(
            Node::class,
            'storage_to_node',
            'storage_id',
            'node_id',
        )->withPivot('backup_order');
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
