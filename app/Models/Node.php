<?php

namespace Convoy\Models;

use Convoy\Rules\Network\Hostname;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'cluster', 'hostname', 'token_id', 'secret', 'port'
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
        'token_id', 'secret'
    ];

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function addresses()
    {
        return $this->hasMany(IPAddress::class);
    }
}