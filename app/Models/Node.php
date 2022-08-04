<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Node extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name', 'hostname', 'username', 'password', 'token_id', 'secret', 'port', 'auth_type', 'latency', 'last_pinged'
    ];

    protected $hidden = [
        'username', 'password',
    ];

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function addresses()
    {
        return $this->hasMany(IPAddress::class);
    }

    public function toSearchableArray()
    {
        return [
            'name' => $this->name
        ];
    }
}