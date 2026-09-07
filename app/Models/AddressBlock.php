<?php

namespace App\Models;

use App\Enums\Network\AddressVersion;
use App\Models\Concerns\CountsAddressStates;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use IPLib\Factory as IPFactory;
use IPLib\Range\RangeInterface;

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
    use CountsAddressStates;

    public $timestamps = false;

    /** `version` is derived from base_ip — a database-generated column that cannot be written to. */
    protected $guarded = ['id', 'version'];

    public static array $validationRules = [
        'address_block_group_id' => 'required|integer|exists:address_block_groups,id',
        'name' => 'nullable|string|max:40',
        'description' => 'nullable|string|max:191',
        'base_ip' => 'required|ip',
        'gateway' => 'nullable|ip',
        'mac_address' => 'nullable|mac_address',
        'prefix_length_from' => 'required|integer|min:0|max:128',
        'prefix_length_to' => 'required|integer|min:0|max:128',
    ];

    /**
     * Read from base_ip rather than the column so an unsaved block — the geometry validator builds
     * one to test a submitted block before it exists — answers the same as a persisted one. The
     * column is the database's own copy of this derivation and is only there for SQL filters.
     */
    protected function version(): Attribute
    {
        return Attribute::get(function (?string $stored): AddressVersion {
            $baseIp = $this->attributes['base_ip'] ?? null;

            // base_ip wasn't selected; fall back to the generated column, which cannot disagree.
            if ($baseIp === null) {
                return AddressVersion::from((string) $stored);
            }

            return str_contains($baseIp, ':') ? AddressVersion::IPv6 : AddressVersion::IPv4;
        });
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
     * How many units this block can ever hand out, or null when that count is not representable.
     *
     * A sparse block's 2^n runs past a PHP int (a /64 delegating /128s is 2^64) and is never
     * materialized anyway, so there is no denominator to report — null says "unknown", which is
     * different from a total of zero and has to stay different all the way to the meter.
     */
    public function totalUnits(): ?int
    {
        return $this->isSparse() ? null : 1 << $this->allocatableHostBits();
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

    /** The first address of the block's overall range — its network address for v4. */
    public function firstAllocatableAddress(): string
    {
        return $this->blockRange()->getStartAddress()->toString();
    }

    /** The last address of the block's overall range (its broadcast for v4), the ceiling for minting. */
    public function lastAllocatableAddress(): string
    {
        return $this->blockRange()->getEndAddress()->toString();
    }

    /**
     * Where $ip sits in the block's run of allocatable units, counting from zero.
     *
     * This is what lets the address map place a materialized address on the grid without
     * re-deriving the whole unit sequence: generation writes units in address order, but a
     * deletion leaves a hole, so position cannot be inferred from row order. GMP because a v6
     * offset does not fit in a PHP int even when the index does.
     *
     * Null when $ip is outside the block, or its index is past what an int can hold.
     */
    public function unitIndexOf(string $ip): ?int
    {
        $start = inet_pton($this->firstAllocatableAddress());
        $target = inet_pton($ip);

        if ($start === false || $target === false || strlen($start) !== strlen($target)) {
            return null;
        }

        $offset = gmp_sub(gmp_import($target), gmp_import($start));

        if (gmp_sign($offset) < 0) {
            return null;
        }

        $index = gmp_div_q($offset, gmp_init((string) $this->unitStride()));

        return gmp_cmp($index, gmp_init((string) PHP_INT_MAX)) > 0
            ? null
            : gmp_intval($index);
    }

    /**
     * The address of the unit at $index, whether or not a row exists for it.
     *
     * The inverse of `unitIndexOf`. The map needs this because a unit is a real position in the
     * block before anything is generated into it: without an address, an ungenerated cell can only
     * be labelled by its offset, and "#32" tells an operator nothing they can act on.
     */
    public function unitAddressAt(int $index): ?string
    {
        $start = inet_pton($this->firstAllocatableAddress());

        if ($start === false || $index < 0) {
            return null;
        }

        $value = gmp_add(
            gmp_import($start),
            gmp_mul(gmp_init((string) $index), gmp_init((string) $this->unitStride())),
        );

        // gmp_export drops leading zero bytes (and returns '' for zero), so pad back to the
        // address width; anything wider has run past the family and is not an address.
        $bytes = str_pad(gmp_export($value), strlen($start), "\0", STR_PAD_LEFT);

        if (strlen($bytes) !== strlen($start)) {
            return null;
        }

        $ip = inet_ntop($bytes);

        return $ip === false ? null : $ip;
    }

    /**
     * The allocatable unit containing $ip — that is, $ip masked down to the block's output prefix.
     * When the block hands out individual addresses (/32, /128) every unit is one address and this
     * is the identity; when it delegates sub-blocks it answers "which sub-block owns this address".
     */
    public function unitContaining(string $ip): ?string
    {
        return IPFactory::parseRangeString($ip.'/'.$this->prefix_length_to)
            ?->getStartAddress()
            ->toString();
    }

    public function containsAddress(string $ip): bool
    {
        $address = IPFactory::parseAddressString($ip);

        return $address !== null && $this->blockRange()->contains($address);
    }

    /**
     * Addresses that must never be handed to a VM and are auto-reserved. Returned at *unit*
     * granularity — generation and minting only ever materialize unit boundaries, so a reservation
     * that isn't itself a unit address silently matches nothing.
     *
     * Which addresses qualify depends on what a unit means for this block:
     *
     *  - **Host allocation** (output prefix /32 or /128): units are individual addresses on a
     *    shared segment, so that segment's network, broadcast and subnet-router anycast are real
     *    hazards and are withheld.
     *  - **Subnet delegation** (output prefix shorter than a single address): each unit is a routed
     *    prefix whose holder manages its own network and broadcast internally, so the parent's are
     *    not ours to withhold — withholding them would lock a /24 → /24 block entirely.
     *
     * The gateway is a hazard under both: it lives inside exactly one unit, and handing that unit
     * over hands over the gateway with it.
     *
     * @return list<string>
     */
    public function systemReservedAddresses(): array
    {
        $reserved = [];

        if ($this->prefix_length_to === $this->maxPrefixLength()) {
            if ($this->version === AddressVersion::IPv4) {
                // RFC 3021: a /31 point-to-point link has neither a network nor a broadcast address.
                if ($this->prefix_length_from <= 30) {
                    $reserved[] = $this->firstAllocatableAddress(); // network
                    $reserved[] = $this->lastAllocatableAddress();  // broadcast
                }
            } else {
                $reserved[] = $this->firstAllocatableAddress();     // subnet-router anycast
            }
        }

        // A gateway outside the block (an upstream router on a different prefix) owns no unit here,
        // so masking it would invent a reservation for an address this block never hands out.
        if ($this->gateway && $this->containsAddress($this->gateway)) {
            $gatewayUnit = $this->unitContaining($this->gateway);

            if ($gatewayUnit !== null) {
                $reserved[] = $gatewayUnit;
            }
        }

        return array_values(array_unique($reserved));
    }

    private function blockRange(): RangeInterface
    {
        $range = IPFactory::parseRangeString($this->base_ip.'/'.$this->prefix_length_from);

        if ($range === null) {
            throw new \RuntimeException("Address block {$this->id} has an unparseable range ({$this->base_ip}/{$this->prefix_length_from}).");
        }

        return $range;
    }

    protected function macAddress(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Str::lower($value) : null,
            set: fn (?string $value) => $value ? Str::lower($value) : null,
        );
    }
}
