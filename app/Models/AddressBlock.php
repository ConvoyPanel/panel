<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Enums\Network\AddressVersion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $address_block_group_id
 * @property ?string $name
 * @property ?string $description
 * @property AddressVersion $version
 * @property string $base_ip
 * @property ?string $gateway
 * @property ?string $mac_address
 * @property int $prefix_length_from
 * @property int $prefix_length_to
 * @property int $addresses_count
 * @property AddressBlockGroup $addressBlockGroup
 * @property Collection<int, Address> $addresses
 */
class AddressBlock extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public static array $validationRules = [
        'address_block_group_id' => 'required|integer|exists:address_block_groups,id',
        'name' => 'nullable|string|max:40',
        'description' => 'nullable|string|max:191',
        'version' => 'in:ipv4,ipv6|required',
        'base_ip' => 'required|ip',
        'gateway' => 'nullable|ip',
        'mac_address' => 'nullable|mac_address',
        'prefix_length_from' => 'required|integer|min:0|max:128',
        'prefix_length_to' => 'required|integer|min:0|max:128',
    ];

    public function casts(): array
    {
        return [
            'version' => AddressVersion::class,
        ];
    }

    /**
     * @return BelongsTo<AddressBlockGroup, $this>
     */
    public function addressBlockGroup(): BelongsTo
    {
        return $this->belongsTo(AddressBlockGroup::class);
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected function macAddress(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Str::lower($value) : null,
            set: fn (?string $value) => $value ? Str::lower($value) : null,
        );
    }
}
