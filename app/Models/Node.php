<?php

namespace Convoy\Models;

use Convoy\Casts\MebibytesToAndFromBytes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Node extends Model
{
    use HasFactory;

    public const COTERM_TOKEN_ID_LENGTH = 16;
    public const COTERM_TOKEN_LENGTH = 64;

    protected $casts = [
        'memory' => MebibytesToAndFromBytes::class,
        'disk' => MebibytesToAndFromBytes::class,
        'secret' => 'encrypted',
        'coterm_enabled' => 'boolean',
        'coterm_tls_enabled' => 'boolean',
        'coterm_token' => 'encrypted',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static $validationRules = [
        'location_id' => 'required|integer|exists:locations,id',
        'name' => 'required|string|max:191',
        'cluster' => 'required|string|max:191',
        'fqdn' => 'required|string|max:191',
        'token_id' => 'required|string|max:191',
        'secret' => 'required|string|max:191',
        'port' => 'required|integer|min:1|max:65535',
        'memory' => 'required|integer',
        'memory_overallocate' => 'required|integer',
        'disk' => 'required|integer',
        'disk_overallocate' => 'required|integer',
        'vm_storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'backup_storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'iso_storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'network' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'coterm_enabled' => 'sometimes|boolean',
        'coterm_tls_enabled' => 'sometimes|boolean',
        'coterm_fqdn' => 'nullable|string|max:191',
        'coterm_port' => 'sometimes|integer',
        'coterm_token_id' => 'required_if:coterm_enabled,1',
        'coterm_token' => 'required_if:coterm_enabled,1',
    ];

    protected $hidden = [
        'token_id', 'secret', 'coterm_token_id', 'coterm_token',
    ];

    /**
     * Get the connection address to use when making calls to this node.
     */
    public function getCotermConnectionAddress(): string
    {
        return sprintf('%s://%s:%s', $this->coterm_tls_enabled ? 'https' : 'http', $this->coterm_fqdn, $this->coterm_port);
    }

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
     */
    public function location(): BelongsTo
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
