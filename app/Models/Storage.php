<?php

namespace App\Models;

use App\Casts\StorageSizeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
            'is_shareable' => 'boolean',
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
     * Get the backups stored on this storage.
     */
    public function backups(): HasMany
    {
        // Assumes 'storage_id' foreign key exists on the 'backups' table
        return $this->hasMany(Backup::class);
    }

    /**
     * Get the snapshots associated with servers residing on this storage.
     * Storage -> Server -> Snapshot
     */
    public function snapshots(): HasManyThrough
    {
        return $this->hasManyThrough(
            Snapshot::class, // Final model we want
            Server::class,   // Intermediate model
            'storage_id',    // Foreign key on Server table (connecting Storage to Server)
            'server_id',     // Foreign key on Snapshot table (connecting Server to Snapshot)
            'id',            // Local key on Storage table
            'id'             // Local key on Server table
        );
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
