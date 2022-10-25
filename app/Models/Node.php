<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'cluster', 'hostname', 'token_id', 'secret', 'port', 'network', 'storage',
    ];

    public static $validationRules = [
        'name' => 'required|string|max:191',
        'cluster' => 'required|string|max:191',
        'hostname' => 'required|string',
        'token_id' => 'required|string',
        'secret' => 'required|string',
        'port' => 'required|integer',
        'network' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
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

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
