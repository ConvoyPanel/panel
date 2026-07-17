<?php

namespace App\Models;

use App\Casts\OveragePenaltyCast;
use App\Casts\StorageSizeCast;
use App\Data\Server\OveragePenaltyData;
use App\Enums\Node\NodeStatus;
use App\Enums\Node\Testing\ConnectionErrorCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property int $location_id
 * @property string $display_name
 * @property string $name
 * @property bool $verify_tls
 * @property NodeStatus $status
 * @property ConnectionErrorCode|null $status_code
 * @property string|null $status_message
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $status_checked_at
 * @property int $consecutive_failures
 * @property string $fqdn
 * @property string $cluster
 * @property int $port
 * @property int $socket_count
 * @property int $core_count
 * @property int $cpu_count
 * @property int $memory
 * @property int $memory_allocated
 * @property int $memory_overallocate
 * @property int $disk
 * @property int $disk_allocated
 * @property int $disk_overallocate
 * @property int|null $anchor_id
 * @property ?OveragePenaltyData $overage_penalty
 * @property ?Anchor $anchor
 */
class Node extends Model
{
    use HasFactory, HasRelationships;

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = [
        'token_id',
        'token_secret',
    ];

    /**
     * Fields that aren't mass assignable
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static array $validationRules = [
        'location_id' => 'required|integer|exists:locations,id',
        'display_name' => 'required|string|max:191',
        'name' => 'required|string|max:191',
        'verify_tls' => 'sometimes|boolean',
        'fqdn' => 'required|string|max:191',
        'token_id' => 'required|string|max:191',
        'token_secret' => 'required|string|max:191',
        'port' => 'required|integer|min:1|max:65535',
        'socket_count' => 'required|integer|min:1',
        'core_count' => 'required|integer|min:1',
        'cpu_count' => 'required|integer|min:1',
        'memory' => 'required|integer',
        'memory_overallocate' => 'required|integer',
        // 'network' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'anchor_id' => 'sometimes|nullable|integer|exists:anchors,id',
        // Per-node override of the quota-overage penalty; null = inherit the global
        // BandwidthSettings default. See docs/bandwidth-rate-limiting-plan.md §5.
        'overage_penalty' => 'sometimes|nullable|array',
        'overage_penalty.action' => 'required_with:overage_penalty|string|in:throttle,disconnect',
        'overage_penalty.rate' => 'nullable|integer|min:1',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verify_tls' => 'boolean',
            'memory' => StorageSizeCast::class,
            'token_secret' => 'encrypted',
            'overage_penalty' => OveragePenaltyCast::class,
            'status' => NodeStatus::class,
            'status_code' => ConnectionErrorCode::class,
            'last_seen_at' => 'datetime',
            'status_checked_at' => 'datetime',
            'consecutive_failures' => 'integer',
        ];
    }

    /**
     * How long a recorded status stays trustworthy.
     *
     * `nodes:poll` runs every minute, so this is generous enough to survive a
     * skipped pass or a briefly backed-up queue.
     */
    public const STATUS_TTL_MINUTES = 5;

    /**
     * The stored status, degraded to `unknown` once the last check is too old
     * to stand behind.
     *
     * Without this, an install whose scheduler or queue worker has stopped
     * would keep reporting whatever was true when it last ran — a node could
     * read `online` for weeks after it burned down. A remembered answer is not
     * an observation, and the difference matters most exactly when the
     * monitoring itself is broken.
     */
    public function currentStatus(): NodeStatus
    {
        if (
            $this->status_checked_at === null
            || $this->status_checked_at->lt(now()->subMinutes(self::STATUS_TTL_MINUTES))
        ) {
            return NodeStatus::UNKNOWN;
        }

        return $this->status;
    }

    /**
     * Gets the servers associated with a node.
     */
    /**
     * @return HasMany<Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Gets all the addresses allocated to a node, resolved through the node's
     * network interfaces → address block groups → address blocks.
     */
    public function addresses(): HasManyDeep
    {
        return $this->hasManyDeep(
            Address::class,
            [NetworkInterface::class, 'address_block_group_to_network_interface', AddressBlockGroup::class, AddressBlock::class],
            [
                'node_id',                // network_interfaces.node_id  → nodes.id
                'network_interface_id',   // pivot.network_interface_id  → network_interfaces.id
                'id',                     // address_block_groups.id      ← pivot.address_block_group_id
                'address_block_group_id', // address_blocks.address_block_group_id → address_block_groups.id
                'address_block_id',       // addresses.address_block_id   → address_blocks.id
            ],
            [
                'id',                     // nodes.id
                'id',                     // network_interfaces.id
                'address_block_group_id', // pivot.address_block_group_id
                'id',                     // address_block_groups.id
                'id',                     // address_blocks.id
            ],
        );
    }

    /**
     * Gets the ISOs downloaded on a node.
     */
    public function isos(): HasManyDeep
    {
        return $this->hasManyDeep(
            ISO::class, // The final related model
            ['storage_to_node', Storage::class], // Intermediate models/tables [pivot, related]
            [
                'node_id',    // Foreign key on the 'storage_to_node' pivot table for the Node model
                'id',         // Foreign key on the 'storages' table (belongs to Storage model)
                'storage_id',  // Foreign key on the 'iso_library' table for the Storage model
            ],
            [
                'id',         // Local key on the 'nodes' table
                'storage_id', // Local key on the 'storage_to_node' pivot table for the Storage model
                'id',          // Local key on the 'storages' table
            ]
        );
    }

    /**
     * Gets the location associated with a node.
     */
    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<NetworkInterface, $this>
     */
    public function networkInterfaces(): HasMany
    {
        return $this->hasMany(NetworkInterface::class);
    }

    /**
     * Gets the Anchor agent connected with this node.
     */
    /**
     * @return BelongsTo<Anchor, $this>
     */
    public function anchor(): BelongsTo
    {
        return $this->belongsTo(Anchor::class);
    }

    /**
     * @return BelongsToMany<Storage, $this>
     */
    public function storages(): BelongsToMany
    {
        return $this->belongsToMany(
            Storage::class,
            'storage_to_node',
            'node_id',
            'storage_id',
        )
            ->withPivot('backup_order');
    }

    /**
     * A storage on this node capable of holding ISOs. Used as the default when
     * uploading a new ISO (the user may override the selection).
     */
    public function isoStorage(): ?Storage
    {
        return $this->storages()->where('stores_iso', true)->first();
    }

    /**
     * A storage on this node capable of holding backups.
     */
    public function backupStorage(): ?Storage
    {
        return $this->storages()->where('stores_backups', true)->first();
    }

    /**
     * Gets the total memory used from adding up all the associated servers' allocated memory.
     */
    public function getMemoryAllocatedAttribute(): int
    {
        return $this->servers->sum('memory');
    }

    /**
     * The column Laravel should look at for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
