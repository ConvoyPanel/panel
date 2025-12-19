<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function addressBlockGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            AddressBlockGroup::class,
            'address_block_group_to_network_interface',
            'network_interface_id',
            'address_block_group_id'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
