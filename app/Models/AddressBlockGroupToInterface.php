<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * This is a pivot table for linking address pools to nodes in a many-to-many relation.
 * An address pool can be allocated to multiple nodes. Similarly, multiple nodes can
 * be allocated to a single address pool.
 */
class AddressBlockGroupToInterface extends Model
{
    protected $table = 'address_block_group_to_network_interface';

    public $guarded = [];

    public $timestamps = false;

    // Pivot table has no auto-incrementing id; without this postgres errors on
    // insert ... returning "id".
    public $incrementing = false;

    public function addressBlockGroup(): BelongsTo
    {
        return $this->belongsTo(AddressBlockGroup::class);
    }

    public function addresses(): HasManyThrough
    {
        return $this->hasManyThrough(Address::class, AddressBlockGroup::class);
    }
}
