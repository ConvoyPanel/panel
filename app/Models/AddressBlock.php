<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Enums\Network\AddressVersion;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use IPLib\Factory as IPFactory;

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

    /**
     * Blocks with more allocatable units than this are stored *sparsely* — their addresses are
     * minted on demand by the allocator instead of being pre-materialized, since a large v4 block
     * (or any v6 block) would be billions of rows. See AddressAllocationService / GenerateAddressesAction.
     */
    public const DENSE_MAX_HOST_BITS = 16; // 2^16 = 65,536 units materialized at most

    public function maxPrefixLength(): int
    {
        return $this->version === AddressVersion::IPv4 ? 32 : 128;
    }

    /** Number of allocatable units = 2^(prefix_to - prefix_from). */
    public function allocatableHostBits(): int
    {
        return $this->prefix_length_to - $this->prefix_length_from;
    }

    public function isSparse(): bool
    {
        return $this->allocatableHostBits() > self::DENSE_MAX_HOST_BITS;
    }

    /**
     * The address distance between consecutive allocatable units (1 for individual addresses,
     * 2^(maxbits - prefix_to) for sub-blocks). Kept within bigint so Postgres inet arithmetic works.
     */
    public function unitStride(): int
    {
        $exponent = $this->maxPrefixLength() - $this->prefix_length_to;

        if ($exponent < 0 || $exponent > 62) {
            throw new \RuntimeException("Address block {$this->id} has an unsupported unit stride (2^{$exponent}).");
        }

        return 1 << $exponent;
    }

    /** The last address of the block's overall range (its broadcast for v4), the ceiling for minting. */
    public function lastAllocatableAddress(): string
    {
        return IPFactory::parseRangeString($this->base_ip . '/' . $this->prefix_length_from)
            ->getEndAddress()
            ->toString();
    }

    protected function macAddress(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Str::lower($value) : null,
            set: fn (?string $value) => $value ? Str::lower($value) : null,
        );
    }
}
