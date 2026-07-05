<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property string $name
 * @property ?string $description
 * @property Collection<int, AddressBlock> $addressBlocks
 */
class AddressBlockGroup extends Model
{
    use HasFactory, HasRelationships;

    public $timestamps = false;

    protected $guarded = ['id'];

    public static array $validationRules = [
        'name' => 'required|string|max:40',
        'description' => 'nullable|string|max:191',
    ];

    /**
     * Gets the nodes that this address block group is connected to via network interfaces.
     */
    public function nodes(): HasManyDeep
    {
        return $this->hasManyDeep(
            Node::class,
            ['address_block_group_to_network_interface', NetworkInterface::class],
            [
                'address_block_group_id', // Foreign key on the pivot table
                'id',                     // Foreign key on the network_interfaces table
                'id'                      // Local key on the nodes table
            ],
            [
                'id',                     // Local key on the address_block_groups table
                'network_interface_id',   // Foreign key on the pivot table
                'node_id'                 // Foreign key on the network_interfaces table
            ]
        );
    }

    /**
     * @return HasMany<AddressBlock, $this>
     */
    public function addressBlocks(): HasMany
    {
        return $this->hasMany(AddressBlock::class);
    }

    /**
     * Gets the network interfaces this address block group is allocated to.
     */
    public function networkInterfaces(): BelongsToMany
    {
        return $this->belongsToMany(
            NetworkInterface::class,
            'address_block_group_to_network_interface',
            'address_block_group_id',
            'network_interface_id'
        );
    }

    /**
     * The column Laravel should look at for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
