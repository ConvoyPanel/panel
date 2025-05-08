<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AddressBlockGroup extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public static array $validationRules = [
        'name' => 'required|string|max:191',
        'description' => 'nullable|string|max:191',
    ];

    /**
     * Gets the nodes that an address pool is allocated to.
     */
    public function nodes(): BelongsToMany
    {
        return $this->belongsToMany(
            Node::class,
            'address_block_group_to_node',
            'address_block_group_id',
            'node_id',
        );
    }

    public function addressBlocks(): HasMany
    {
        return $this->hasMany(AddressBlock::class);
    }

    /**
     * The column Laravel should look at for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
