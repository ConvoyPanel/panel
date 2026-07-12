<?php

namespace App\Models;

use App\Casts\OveragePenaltyCast;
use App\Casts\StorageSizeCast;
use App\Data\Server\OveragePenaltyData;
use App\Enums\Server\ServerStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $node_id
 * @property ?int $network_interface_id
 * @property int $vmid
 * @property string $uuid
 * @property string $uuid_short
 * @property string $hostname
 * @property string $name
 * @property ?string $description
 * @property ServerStatus $status
 * @property int $cpu
 * @property int $memory
 * @property int $disk
 * @property int $bandwidth_usage
 * @property int $backup_count_limit
 * @property int $backup_size_limit
 * @property int $bandwidth_limit
 * @property ?int $speed_limit
 * @property ?OveragePenaltyData $overage_penalty
 * @property ?int $bandwidth_reset_day
 * @property ?int $vlan_tag
 * @property Node $node
 * @property ?NetworkInterface $networkInterface
 * @property Storage $storage
 * @property Collection<int, ServerDisk> $disks
 * @property ?ServerDisk $primaryDisk
 * @property ?Address $primaryIPv4Address
 * @property ?Address $primaryIPv6Address
 */
class Server extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = [
        'id',
        'uuid',
        'uuid_short',
        'created_at',
    ];

    public static array $validationRules = [
        'name' => 'required|string|min:1|max:40',
        'node_id' => 'required|integer|exists:nodes,id',
        'storage_id' => 'required|integer|exists:storages,id',
        'network_interface_id' => 'nullable|integer|exists:network_interfaces,id',
        'user_id' => 'required|integer|exists:users,id',
        'vmid' => 'required|numeric|min:100|max:999999999',
        'hostname' => 'required|string|min:1|max:191',
        'status' => ['sometimes', 'nullable', 'string', 'in:ready,deferred_os_selection,installing,install_failed,suspended,restoring_backup,deleting,deletion_failed'],
        'cpu' => 'required|numeric|min:1',
        'memory' => 'required|numeric|min:16777216',
        'disk' => 'required|numeric|min:1',
        'bandwidth_usage' => 'sometimes|numeric|min:0',
        'backup_count_limit' => 'required|integer|min:-1',
        'backup_size_limit' => 'required|integer|min:-1',
        'bandwidth_limit' => 'present|integer|min:-1',
        // Persistent NIC speed cap in bytes/s (null = unlimited); the request layer
        // converts the operator's MB/s input. See docs/bandwidth-rate-limiting-plan.md.
        'speed_limit' => 'sometimes|nullable|integer|min:0',
        // Per-server override of the quota-overage penalty; null = inherit. Nested
        // shape is validated where it's exposed (UpdateBuildRequest).
        'overage_penalty' => 'sometimes|nullable|array',
        'overage_penalty.action' => 'required_with:overage_penalty|string|in:throttle,disconnect',
        'overage_penalty.rate' => 'nullable|integer|min:1',
        'bandwidth_reset_day' => 'sometimes|nullable|integer|min:1|max:31',
        'vlan_tag' => 'nullable|integer|min:1|max:4094',
        'hydrated_at' => 'nullable|date',
    ];

    protected function casts(): array
    {
        return [
            'status' => ServerStatus::class,
            'memory' => StorageSizeCast::class,
            'disk' => StorageSizeCast::class,
            'bandwidth_usage' => StorageSizeCast::class,
            'bandwidth_limit' => StorageSizeCast::class,
            'backup_size_limit' => StorageSizeCast::class,
            'speed_limit' => 'integer',
            'overage_penalty' => OveragePenaltyCast::class,
            'bandwidth_reset_day' => 'integer',
            'vlan_tag' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Node, $this>
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * @return BelongsTo<NetworkInterface, $this>
     */
    public function networkInterface(): BelongsTo
    {
        return $this->belongsTo(NetworkInterface::class);
    }

    /**
     * The server's primary/boot storage. Expand-first: this column is still the
     * source of truth for the primary disk's storage (the template clone reads
     * it). It is mirrored by the `is_primary` row in {@see disks()}.
     *
     * @return BelongsTo<Storage, $this>
     */
    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    /**
     * All of the server's disks (one primary + zero or more secondary).
     *
     * @return HasMany<ServerDisk, $this>
     */
    public function disks(): HasMany
    {
        return $this->hasMany(ServerDisk::class);
    }

    /**
     * The primary/boot disk row.
     *
     * @return HasOne<ServerDisk, $this>
     */
    public function primaryDisk(): HasOne
    {
        return $this->hasOne(ServerDisk::class)->where('is_primary', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope the query to servers the given user owns.
     *
     * This is the single source of truth for client-facing server visibility.
     * Ownership is deliberate for everyone, including root admins — the client
     * area shows a user their own servers, not every server on the panel (use
     * the admin area for that). When subuser support is added, extend the
     * ownership check here (e.g. an orWhereHas on a subusers relation) and
     * every listing inherits it.
     *
     * @param  Builder<Server>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function template(): HasOne
    {
        return $this->hasOne(Template::class);
    }

    /**
     * @return HasMany<Backup, $this>
     */
    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    /**
     * @return HasMany<Deployment, $this>
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function primaryIPv4Address(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'primary_ipv4_address_id');
    }

    public function primaryIPv6Address(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'primary_ipv6_address_id');
    }

    /**
     * Returns all the activity log entries where the server is the subject.
     */
    public function activity(): MorphToMany
    {
        return $this->morphToMany(ActivityLog::class, 'subject', 'activity_log_subjects');
    }

    /**
     * Whether the server has blown its monthly bandwidth quota. A negative
     * `bandwidth_limit` (the -1 sentinel) means unlimited and is never "over".
     */
    public function isOverBandwidthQuota(): bool
    {
        return $this->bandwidth_limit >= 0
            && $this->bandwidth_usage >= $this->bandwidth_limit;
    }

    /**
     * The day-of-month (1-31) the monthly quota resets on, falling back to the
     * server's creation day when no explicit anchor is stored.
     */
    public function bandwidthResetDay(): int
    {
        return $this->bandwidth_reset_day ?? $this->created_at->day;
    }

    public function isInstalled(): bool
    {
        return $this->status->isInstalled();
    }

    public function isInstalling(): bool
    {
        return $this->status->isInstalling();
    }

    public function isSuspended(): bool
    {
        return $this->status->isSuspended();
    }

    public function isReady(): bool
    {
        return $this->status->isReady();
    }
}
