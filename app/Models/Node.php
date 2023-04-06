<?php

namespace Convoy\Models;

use Convoy\Casts\MebibytesToAndFromBytes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Node extends Model
{
    use HasFactory;

    protected $casts = [
        'memory' => MebibytesToAndFromBytes::class,
        'disk' => MebibytesToAndFromBytes::class,
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static $validationRules = [
        'location_id' => 'required|integer|exists:locations,id',
        'name' => 'required|string|max:191',
        'cluster' => 'required|string|max:191',
        'fqdn' => 'required|string|max:191',
        'token_id' => 'required|string|max:191',
        'secret' => 'required|string|max:191',
        'port' => 'required|integer',
        'memory' => 'required|integer',
        'memory_overallocate' => 'required|integer',
        'disk' => 'required|integer',
        'disk_overallocate' => 'required|integer',
        'vm_storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'backup_storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'iso_storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'network' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
    ];

    protected $hidden = [
        'token_id', 'secret',
    ];

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function addresses()
    {
        return $this->hasMany(IPAddress::class);
    }

    public function templateGroups()
    {
        return $this->hasMany(TemplateGroup::class);
    }

    public function isos()
    {
        return $this->hasMany(ISO::class);
    }

    /**
     * Gets the location associated with a node.
     *
     * @return BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function getDiskAllocatedAttribute()
    {
        return $this->servers->sum('disk');
    }

    public function getMemoryAllocatedAttribute()
    {
        return $this->servers->sum('memory');
    }
}
