<?php

namespace Convoy\Models;

use Convoy\Casts\MegabytesAndBytes;

class Backup extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'size' => MegabytesAndBytes::class,
    ];

    protected $dates = [
        'completed_at',
    ];

    public static $validationRules = [
        'uuid' => 'required|uuid',
        'server_id' => 'required|exists:servers,id',
        'successful' => 'sometimes|boolean',
        'locked' => 'sometimes|boolean',
        'name' => 'required|string|min:1|max:40',
        'file_name' => 'nullable|string',
        'size' => 'sometimes|numeric|min:0',
        'completed_at' => 'nullable|date',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
