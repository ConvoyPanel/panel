<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AddressPool extends Model
{
    /**
     * Fields that aren't mass assignable
     */
    protected $guarded = ['id', 'updated_at', 'created_at'];

    public static $validationRules = [
        'name' => 'required|string|max:191',
    ];

    /**
     * Gets the nodes that an address pool is allocated to.
     */
    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(Node::class, 'address_pool_to_node', 'address_pool_id', 'node_id');
    }

    /**
     * Gets the addresses that are associated with an address pool.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * The column Laravel should look at for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
