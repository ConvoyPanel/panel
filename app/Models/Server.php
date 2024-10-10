<?php

namespace Convoy\Models;

use Convoy\Casts\StorageSizeCast;
use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Http\Server\ServerStatusConflictException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Server extends Model
{
    use HasFactory;

    protected $casts = [
        'memory' => StorageSizeCast::class,
        'disk' => StorageSizeCast::class,
        'bandwidth_usage' => StorageSizeCast::class,
        'bandwidth_limit' => StorageSizeCast::class,
    ];

    protected $guarded = [
        'id',
        'updated_at',
        'created_at',
    ];

    public static array $validationRules = [
        'name' => 'required|string|min:1|max:40',
        'node_id' => 'required|integer|exists:nodes,id',
        'user_id' => 'required|integer|exists:users,id',
        'vmid' => 'required|numeric|min:100|max:999999999',
        'hostname' => 'required|string|min:1|max:191',
        'status' => ['sometimes', 'nullable', 'string', 'in:installing,install_failed,suspended,restoring_backup,restoring_snapshot,deleting,deletion_failed'],
        'cpu' => 'required|numeric|min:1',
        'memory' => 'required|numeric|min:16777216',
        'disk' => 'required|numeric|min:1',
        'bandwidth_usage' => 'sometimes|numeric|min:0',
        'snapshot_count_limit' => 'required|integer|min:-1',
        'snapshot_size_limit' => 'required|integer|min:-1',
        'backup_count_limit' => 'required|integer|min:-1',
        'backup_size_limit' => 'required|integer|min:-1',
        'bandwidth_limit' => 'present|integer|min:-1',
        'hydrated_at' => 'nullable|date',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function template(): HasOne
    {
        return $this->hasOne(Template::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * Returns all the activity log entries where the server is the subject.
     */
    public function activity(): MorphToMany
    {
        return $this->morphToMany(ActivityLog::class, 'subject', 'activity_log_subjects');
    }

    public function isInstalled(): bool
    {
        return $this->status !== Status::INSTALLING->value;
    }

    public function isInstalling(): bool
    {
        return $this->status === Status::INSTALLING->value;
    }

    public function isSuspended(): bool
    {
        return $this->status === Status::SUSPENDED->value;
    }

    /**
     * Checks if the server is currently in a user-accessible state. If not, an
     * exception is raised. This should be called whenever something needs to make
     * sure the server is not in a weird state that should block user access.
     *
     * @throws ServerStatusConflictException
     */
    public function validateCurrentState(): void
    {
        if (
            !is_null($this->status)
        ) {
            throw new ServerStatusConflictException($this);
        }
    }
}
