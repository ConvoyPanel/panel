<?php

namespace App\Models;

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property int $address_block_id
 * @property ?int $server_id
 * @property AddressState $state
 * @property AddressVersion $version
 * @property string $ip
 * @property int $prefix_length
 * @property ?string $gateway
 * @property ?string $mac_address
 * @property AddressBlock $addressBlock
 * @property ?Server $server
 */
class Address extends Model
{
    use HasFactory, HasRelationships;

    public $timestamps = false;

    protected $guarded = ['id'];

    public static array $validationRules = [
        'address_block_id' => ['exists:address_blocks,id', 'required'],
        'server_id' => ['exists:servers,id', 'nullable'],
        'ip' => ['ip'],
        'prefix_length' => ['numeric', 'min:0', 'max:128', 'required'],
    ];

    public function casts(): array
    {
        return [
            'state' => AddressState::class,
        ];
    }

    /**
     * @return BelongsTo<AddressBlock, $this>
     */
    public function addressBlock(): BelongsTo
    {
        return $this->belongsTo(AddressBlock::class);
    }

    public function addressBlockGroup(): HasOneDeep
    {
        return $this->hasOneDeep(
            AddressBlockGroup::class,
            [AddressBlock::class],
            [
                'id',
                'id',
            ],
            [
                'address_block_id',
                'address_block_group_id',
            ]);
    }

    public function networkInterfaces(): HasManyDeep
    {
        return $this->hasManyDeep(
            NetworkInterface::class,
            [AddressBlock::class, AddressBlockGroup::class, 'address_block_group_to_network_interface'],
            [
                'id',
                'id',
                'address_block_group_id',
                'id',
            ],
            [
                'address_block_id',
                'address_block_group_id',
                'id',
                null,
            ]
        );
    }

    /**
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeWithIPv4(Builder $query): Builder
    {
        return $query->whereHas('addressBlock', function (Builder $query) {
            $query->where('version', AddressVersion::IPv4);
        });
    }

    public function scopeWithIPv6(Builder $query): Builder
    {
        return $query->whereHas('addressBlock', function (Builder $query) {
            $query->where('version', AddressVersion::IPv6);
        });
    }

    public function getVersionAttribute(): AddressVersion
    {
        return $this->addressBlock->version;
    }

    public function getGatewayAttribute(): ?string
    {
        return $this->addressBlock->gateway;
    }

    public function getMacAddressAttribute(): ?string
    {
        return $this->addressBlock->mac_address;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
