<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'visible',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
