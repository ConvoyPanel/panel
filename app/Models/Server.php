<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Server extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'user_id',
        'node_id',
        'vmid',
        'installing'
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

    public function toSearchableArray()
    {
        return [
            'name' => $this->name
        ];
    }
}