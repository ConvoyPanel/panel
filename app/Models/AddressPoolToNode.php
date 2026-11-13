<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * This is a pivot table for linking address pools to nodes in a many-to-many relation.
 * An address pool can be allocated to multiple nodes. Similarly, multiple nodes can
 * be allocated to a single address pool.
 */
class AddressPoolToNode extends Model
{
    protected $table = 'address_pool_to_node';

    public $fillable = [
        'address_pool_id',
        'node_id',
    ];

    public $timestamps = false;

    /**
     * The table has no id column. Left incrementing, Eloquent inserts with
     * `RETURNING "id"` on Postgres, which fails; MySQL never asked.
     */
    public $incrementing = false;

    protected $primaryKey = null;

    public function addressPool(): BelongsTo
    {
        return $this->belongsTo(AddressPool::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function addresses(): HasManyThrough
    {
        return $this->hasManyThrough(Address::class, AddressPool::class);
    }
}
