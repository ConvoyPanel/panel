<?php

namespace Convoy\Models;

use Convoy\Casts\MegabytesAndBytes;
use Convoy\Enums\Server\Status;
use Convoy\Exceptions\Http\Server\ServerStateConflictException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Server extends Model
{
    use HasFactory;

    protected $casts = [
        'memory' => MegabytesAndBytes::class,
        'disk' => MegabytesAndBytes::class,
        'bandwidth_usage' => MegabytesAndBytes::class,
        'bandwidth_limit' => MegabytesAndBytes::class,
    ];

    protected $guarded = [
        'id',
        'updated_at',
        'created_at',
    ];

    public static $validationRules = [
        'name' => 'required|string|min:1|max:40',
        'node_id' => 'required|integer|exists:nodes,id',
        'user_id' => 'required|integer|exists:users,id',
        'vmid' => 'required|numeric|min:100|max:999999999',
        'hostname' => 'required|string|min:1|max:191',
        'status' => 'sometimes|nullable|string',
        'installing' => 'sometimes|boolean',
        'address_ids' => 'sometimes|array',
        'address_ids.*' => 'exists:ip_addresses,id',
        'cpu' => 'required|numeric|min:1',
        'memory' => 'required|numeric|min:16777216',
        'disk' => 'required|numeric|min:1',
        'bandwidth_usage' => 'sometimes|numeric|min:0',
        'snapshot_limit' => 'present|nullable|integer|min:0',
        'backup_limit' => 'present|nullable|integer|min:0',
        'bandwidth_limit' => 'present|nullable|integer|min:0',
        'template' => 'required_if:type,existing|boolean',
        'visible' => 'required_with:template|boolean',
        'template_id' => 'required_if:type,new|exists:templates,id',
        'hydrated_at' => 'nullable|date',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addresses()
    {
        return $this->hasMany(IPAddress::class);
    }

    public function template()
    {
        return $this->hasOne(Template::class);
    }

    public function backups()
    {
        return $this->hasMany(Backup::class);
    }

    /**
     * Returns all of the activity log entries where the server is the subject.
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
     * @throws \Convoy\Exceptions\Http\Server\ServerStateConflictException
     */
    public function validateCurrentState()
    {
        if (
            !is_null($this->status)
        ) {
            throw new ServerStateConflictException($this);
        }
    }
}
