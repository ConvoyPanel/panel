<?php

use App\Actions\Ipam\GenerateAddressesAction;
use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Network\AddressVersion;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;

/**
 * System reservations are computed at *unit* granularity — the thing a block actually hands out —
 * which differs from a host address whenever prefix_length_to is shorter than /32 or /128.
 */
function makeBlock(array $attributes): AddressBlock
{
    return AddressBlock::factory()->for(AddressBlockGroup::factory())->create($attributes);
}

/** @return array<string, string> ip => "state:reason" for every generated row. */
function generateAndMap(AddressBlock $block): array
{
    app(GenerateAddressesAction::class)->execute($block);

    return $block->addresses()->get()
        ->mapWithKeys(fn ($address) => [
            $address->ip => $address->state->value.':'.($address->state_reason?->value ?? '-'),
        ])
        ->all();
}

describe('host allocation (units are single addresses)', function () {
    it('reserves network, broadcast and gateway on an ipv4 block', function () {
        $block = makeBlock([
            'version' => AddressVersion::IPv4,
            'base_ip' => '192.0.2.0',
            'gateway' => '192.0.2.1',
            'prefix_length_from' => 24,
            'prefix_length_to' => 32,
        ]);

        expect($block->systemReservedAddresses())
            ->toEqualCanonicalizing(['192.0.2.0', '192.0.2.255', '192.0.2.1']);
    });

    it('reserves neither network nor broadcast on a point-to-point /31', function () {
        $block = makeBlock([
            'version' => AddressVersion::IPv4,
            'base_ip' => '192.0.2.0',
            'gateway' => null,
            'prefix_length_from' => 31,
            'prefix_length_to' => 32,
        ]);

        expect($block->systemReservedAddresses())->toBe([]);
        expect(generateAndMap($block))->toBe([
            '192.0.2.0' => 'available:-',
            '192.0.2.1' => 'available:-',
        ]);
    });

    it('reserves the subnet-router anycast on an ipv6 block', function () {
        $block = makeBlock([
            'version' => AddressVersion::IPv6,
            'base_ip' => '2001:db8::',
            'gateway' => '2001:db8::1',
            'prefix_length_from' => 112,
            'prefix_length_to' => 128,
        ]);

        expect($block->systemReservedAddresses())
            ->toEqualCanonicalizing(['2001:db8::', '2001:db8::1']);
    });

    it('masks an unaligned base ip down to the real network address', function () {
        // The block's network address is what generation materializes, so reserving the raw
        // base_ip would miss it entirely.
        $block = makeBlock([
            'version' => AddressVersion::IPv4,
            'base_ip' => '192.0.2.7',
            'gateway' => null,
            'prefix_length_from' => 24,
            'prefix_length_to' => 32,
        ]);

        expect($block->systemReservedAddresses())->toBe(['192.0.2.0', '192.0.2.255']);
    });
});

describe('subnet delegation (units are routed sub-blocks)', function () {
    it('reserves only the sub-block holding the gateway', function () {
        $block = makeBlock([
            'version' => AddressVersion::IPv4,
            'base_ip' => '192.0.2.0',
            'gateway' => '192.0.2.1',
            'prefix_length_from' => 24,
            'prefix_length_to' => 28,
        ]);

        // The gateway lives inside 192.0.2.0/28, so that unit — not the bare gateway address,
        // which is never a unit boundary — is what has to be withheld.
        expect($block->systemReservedAddresses())->toBe(['192.0.2.0']);

        $map = generateAndMap($block);

        expect($map)->toHaveCount(16)
            ->and($map['192.0.2.0'])->toBe('reserved:system')
            ->and($map['192.0.2.16'])->toBe('available:-')
            ->and($map['192.0.2.240'])->toBe('available:-');
    });

    it('withholds nothing when the block has no gateway', function () {
        $block = makeBlock([
            'version' => AddressVersion::IPv4,
            'base_ip' => '192.0.2.0',
            'gateway' => null,
            'prefix_length_from' => 24,
            'prefix_length_to' => 28,
        ]);

        expect($block->systemReservedAddresses())->toBe([]);
        expect(array_unique(array_values(generateAndMap($block))))->toBe(['available:-']);
    });

    it('leaves a single-unit block allocatable', function () {
        // A /24 → /24 delegates the whole /24 as one routed unit. Treating its base address as an
        // unusable network address locked the block entirely.
        $block = makeBlock([
            'version' => AddressVersion::IPv4,
            'base_ip' => '192.0.2.0',
            'gateway' => null,
            'prefix_length_from' => 24,
            'prefix_length_to' => 24,
        ]);

        expect($block->systemReservedAddresses())->toBe([]);
        expect(generateAndMap($block))->toBe(['192.0.2.0' => 'available:-']);
    });

    it('locks a single-unit block whose gateway sits inside that unit', function () {
        $block = makeBlock([
            'version' => AddressVersion::IPv4,
            'base_ip' => '192.0.2.0',
            'gateway' => '192.0.2.1',
            'prefix_length_from' => 24,
            'prefix_length_to' => 24,
        ]);

        expect(generateAndMap($block))->toBe(['192.0.2.0' => 'reserved:system']);
    });

    it('reserves nothing for a gateway outside the block', function () {
        $block = makeBlock([
            'version' => AddressVersion::IPv4,
            'base_ip' => '192.0.2.0',
            'gateway' => '198.51.100.1',
            'prefix_length_from' => 24,
            'prefix_length_to' => 28,
        ]);

        expect($block->systemReservedAddresses())->toBe([]);
    });

    it('reserves the delegated prefix holding an ipv6 gateway', function () {
        $block = makeBlock([
            'version' => AddressVersion::IPv6,
            'base_ip' => '2001:db8::',
            'gateway' => '2001:db8::1',
            'prefix_length_from' => 48,
            'prefix_length_to' => 64,
        ]);

        expect($block->systemReservedAddresses())->toBe(['2001:db8::']);
    });
});

it('marks system reservations with the system reason so they cannot be unreserved', function () {
    $block = makeBlock([
        'version' => AddressVersion::IPv4,
        'base_ip' => '192.0.2.0',
        'gateway' => '192.0.2.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 28,
    ]);

    app(GenerateAddressesAction::class)->execute($block);

    $unit = $block->addresses()->where('ip', '192.0.2.0')->sole();

    expect($unit->state)->toBe(AddressState::Reserved)
        ->and($unit->state_reason)->toBe(AddressStateReason::System);
});
