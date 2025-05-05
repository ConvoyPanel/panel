<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkInterface extends Model
{
    public $timestamps = false;

    protected $guarded = [
        'id',
    ];

    public static array $validationRules = [
        'node_id' => 'required|integer|exists:nodes,id',
        'name' => 'required|string|min:1|max:40',
        'description' => 'nullable|string|max:191',
        'mtu' => 'nullable|integer|min:1|max:65535',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
