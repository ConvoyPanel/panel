<?php

namespace App\Models;

use App\Rules\Network\Hostname;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Node extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name', 'hostname', 'token_id', 'secret', 'port'
    ];

//    public $validationRules = [
//        'name' => 'string|required',
//        'cluster' => 'string|required',
//        'hostname' => [new Hostname(), 'required'],
//        'token_id' => 'string|required',
//        'secret' => 'string|required',
//        'port' => 'integer|required',
//    ];

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