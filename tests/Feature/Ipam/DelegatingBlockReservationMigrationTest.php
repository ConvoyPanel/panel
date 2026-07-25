<?php

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Network\AddressVersion;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;

it('requalifies system reservations on blocks that delegate sub-blocks', function () {
    // Delegating block whose gateway is *not* in the base unit, so the old and new reservations
    // land on different rows.
    $delegating = AddressBlock::factory()->create([
        'version' => AddressVersion::IPv4,
        'base_ip' => '192.0.2.0',
        'gateway' => '192.0.2.100',
        'prefix_length_from' => 24,
        'prefix_length_to' => 28,
    ]);

    // Single-unit block: the whole /24 delegated as one routed prefix.
    $singleUnit = AddressBlock::factory()->create([
        'version' => AddressVersion::IPv4,
        'base_ip' => '198.51.100.0',
        'gateway' => null,
        'prefix_length_from' => 24,
        'prefix_length_to' => 24,
    ]);

    // Host allocation — must come through the migration untouched.
    $hostBlock = AddressBlock::factory()->create([
        'version' => AddressVersion::IPv4,
        'base_ip' => '203.0.113.0',
        'gateway' => '203.0.113.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);

    $make = fn (AddressBlock $b, string $ip, AddressState $state, ?AddressStateReason $reason = null) => Address::factory()->for($b)->create([
        'ip' => $ip,
        'prefix_length' => $b->prefix_length_to,
        'state' => $state,
        'state_reason' => $reason,
    ]);

    // Rows as the old host-granularity rule left them.
    $baseUnit = $make($delegating, '192.0.2.0', AddressState::Reserved, AddressStateReason::System);
    $gatewayUnit = $make($delegating, '192.0.2.96', AddressState::Available);
    $otherUnit = $make($delegating, '192.0.2.16', AddressState::Available);
    $adminHold = $make($delegating, '192.0.2.32', AddressState::Reserved, AddressStateReason::Admin);

    $onlyUnit = $make($singleUnit, '198.51.100.0', AddressState::Reserved, AddressStateReason::System);

    $network = $make($hostBlock, '203.0.113.0', AddressState::Reserved, AddressStateReason::System);
    $gateway = $make($hostBlock, '203.0.113.1', AddressState::Reserved, AddressStateReason::System);
    $host = $make($hostBlock, '203.0.113.50', AddressState::Available);

    (require database_path('migrations/2026_07_25_000000_requalify_system_reservations_on_delegating_blocks.php'))->up();

    $state = fn (Address $a) => $a->refresh()->state->value.':'.($a->state_reason?->value ?? '-');

    expect($state($baseUnit))->toBe('available:-')          // a routed sub-block, not a dead network address
        ->and($state($gatewayUnit))->toBe('reserved:system') // the reservation that used to vanish
        ->and($state($otherUnit))->toBe('available:-')
        ->and($state($adminHold))->toBe('reserved:admin')    // operator holds are left alone
        ->and($state($onlyUnit))->toBe('available:-')        // the /24 → /24 block gets its capacity back
        // Host allocation is unaffected.
        ->and($state($network))->toBe('reserved:system')
        ->and($state($gateway))->toBe('reserved:system')
        ->and($state($host))->toBe('available:-');
});

it('leaves an assigned address alone even if it should now be system-reserved', function () {
    $block = AddressBlock::factory()->create([
        'version' => AddressVersion::IPv4,
        'base_ip' => '192.0.2.0',
        'gateway' => '192.0.2.100',
        'prefix_length_from' => 24,
        'prefix_length_to' => 28,
    ]);

    $server = Server::factory()
        ->for(Node::factory()->for(Location::factory()))
        ->for(Storage::factory())
        ->create();

    $assigned = Address::factory()->for($block)->create([
        'ip' => '192.0.2.96',
        'prefix_length' => 28,
        'state' => AddressState::Assigned,
        'server_id' => $server->id,
    ]);

    (require database_path('migrations/2026_07_25_000000_requalify_system_reservations_on_delegating_blocks.php'))->up();

    // Yanking a live VM's prefix would be a worse failure than the bug being fixed.
    expect($assigned->refresh()->state)->toBe(AddressState::Assigned)
        ->and($assigned->server_id)->toBe($server->id);
});
