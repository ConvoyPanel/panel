<?php

namespace App\Models;

use App\Casts\OveragePenaltyCast;
use App\Casts\StorageSizeCast;
use App\Data\Server\OveragePenaltyData;
use App\Enums\Anchor\AnchorMode;
use App\Enums\Node\ConnectionErrorCode;
use App\Models\Concerns\AnchorInstallation;
use App\Enums\Node\NodeStatus;
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
 * @property ?int $cluster_id
 * @property ?Cluster $cluster
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
 * @property string|null $agent_uuid
 * @property string|null $agent_secret
 * @property string|null $agent_public_url
 * @property string|null $agent_panel_url_override
 * @property int|null $relay_id
 * @property int|null $agent_enrollment_key_id
 * @property string|null $agent_enrollment_token_hash
 * @property Carbon|null $agent_enrollment_expires_at
 * @property Carbon|null $agent_enrolled_at
 * @property Carbon|null $agent_last_seen_at
 * @property string|null $agent_version
 * @property int|null $agent_protocol_min
 * @property int|null $agent_protocol_max
 * @property array<int, string>|null $agent_capabilities
 * @property array<string, mixed>|null $agent_reported_facts
 * @property ?OveragePenaltyData $overage_penalty
 * @property ?Relay $relay
 * @property-read ?StorageToNode $pivot Present when reached through Storage::nodes().
 */
class Node extends Model
{
    use AnchorInstallation, HasFactory, HasRelationships;

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = [
        'token_id',
        'token_secret',
        'agent_secret',
        'agent_enrollment_token_hash',
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
        // The agent installed on this host. Every column is nullable and stays
        // that way: a node upgraded from v4 has no agent at all, and inventing
        // one to satisfy a constraint would record a machine that does not exist.
        'agent_uuid' => 'sometimes|nullable|uuid',
        'agent_public_url' => 'sometimes|nullable|url:http,https|max:2048',
        'agent_panel_url_override' => 'sometimes|nullable|url:http,https|max:2048',
        'relay_id' => 'sometimes|nullable|integer|exists:relays,id',
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
            'agent_secret' => 'encrypted',
            'agent_enrollment_expires_at' => 'datetime',
            'agent_enrolled_at' => 'datetime',
            'agent_last_seen_at' => 'datetime',
            'agent_protocol_min' => 'integer',
            'agent_protocol_max' => 'integer',
            'agent_capabilities' => 'array',
            'agent_reported_facts' => 'array',
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
     * The storage scope this node resolves into: its PVE cluster, or its own
     * singleton scope when standalone. Null only before the first successful
     * poll or registration-time resolution.
     */
    /**
     * @return BelongsTo<Cluster, $this>
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    /**
     * Gets the Anchor agent connected with this node.
     */
    /**
     * @return BelongsTo<Anchor, $this>
     */
    /** @return BelongsTo<Relay, $this> */
    public function relay(): BelongsTo
    {
        return $this->belongsTo(Relay::class, 'relay_id');
    }

    /** @return BelongsTo<AnchorEnrollmentKey, $this> */
    public function agentEnrollmentKey(): BelongsTo
    {
        return $this->belongsTo(AnchorEnrollmentKey::class, 'agent_enrollment_key_id');
    }

    public function anchorName(): string
    {
        return $this->display_name;
    }

    public function anchorUuid(): ?string
    {
        return $this->agent_uuid;
    }

    public function anchorSecret(): ?string
    {
        return $this->agent_secret;
    }

    public function anchorEnrolledAt(): ?Carbon
    {
        return $this->agent_enrolled_at;
    }

    public function anchorLastSeenAt(): ?Carbon
    {
        return $this->agent_last_seen_at;
    }

    public function anchorProtocolMin(): ?int
    {
        return $this->agent_protocol_min;
    }

    public function anchorProtocolMax(): ?int
    {
        return $this->agent_protocol_max;
    }

    public function anchorPublicUrl(): ?string
    {
        return $this->agent_public_url;
    }

    public function anchorPanelUrlOverride(): ?string
    {
        return $this->agent_panel_url_override;
    }

    /** A node's installation is always the agent; a relay is never a node. */
    public function anchorMode(): AnchorMode
    {
        return AnchorMode::AGENT;
    }

    /** @param array<string, mixed> $payload */
    public function recordAnchorHeartbeat(array $payload): void
    {
        // Writes the agent's liveness, never the node's. `last_seen_at` and
        // `status` describe whether Proxmox answers, which stays a separate
        // question -- a running daemon on a host whose API is down must not
        // read as a healthy node.
        $this->update([
            'agent_last_seen_at' => now(),
            'agent_version' => $payload['version'],
            'agent_protocol_min' => $payload['protocol_min'],
            'agent_protocol_max' => $payload['protocol_max'],
            'agent_capabilities' => $payload['capabilities'],
        ]);
    }

    /**
     * @return BelongsToMany<Storage, $this, StorageToNode>
     */
    public function storages(): BelongsToMany
    {
        return $this->belongsToMany(
            Storage::class,
            'storage_to_node',
            'node_id',
            'storage_id',
        )
            ->using(StorageToNode::class)
            ->withPivot('backup_order', 'discovered_total', 'discovered_used', 'discovered_at');
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
     * Whether a backup could be stored at all. Cheaper than backupStorage() when
     * the caller only needs to know that one exists -- the client uses it to
     * disable the create action up front instead of failing the request.
     */
    public function hasBackupStorage(): bool
    {
        return $this->storages()->where('stores_backups', true)->exists();
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
