<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'node_id',
        'vmid',
        'installing'
    ];

    public static $validationRules = [
        'type' => 'required|in:new,existing',
        'name' => 'required|string|min:1|max:40',
        'node_id' => 'required|exists:nodes,id',
        'user_id' => 'required|exists:users,id',
        'vmid' => 'required_if:type,existing|numeric',
        'status' => 'nullable|string|in:suspended,installing',
        'addresses' => 'sometimes|array',
        'addresses.*' => 'exists:ip_addresses,id',
        'cpu' => 'required|numeric|min:1',
        'memory' => 'required|numeric|min:16777216',
        'disk' => 'required|numeric|min:1',
        'is_template' => 'required_if:type,existing|boolean',
        'is_visible' => 'required_with:is_template|boolean',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function owner()
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

    /**
     * Returns all of the activity log entries where the server is the subject.
     */
    public function activity(): MorphToMany
    {
        return $this->morphToMany(ActivityLog::class, 'subject', 'activity_log_subjects');
    }
}
