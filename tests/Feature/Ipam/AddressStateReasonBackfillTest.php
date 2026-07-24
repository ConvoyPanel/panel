<?php

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Network\AddressVersion;
use App\Models\Address;
use App\Models\AddressBlock;
use Illuminate\Support\Facades\Schema;

it('backfills system vs admin reasons from existing reserved rows', function () {
    $v4 = AddressBlock::factory()->create([
        'version' => AddressVersion::IPv4,
        'base_ip' => '192.0.2.0',
        'gateway' => '192.0.2.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);

    // /31 has no network or broadcast (RFC 3021), so only the gateway is structural.
    $p2p = AddressBlock::factory()->create([
        'version' => AddressVersion::IPv4,
        'base_ip' => '203.0.113.0',
        'gateway' => '203.0.113.1',
        'prefix_length_from' => 31,
        'prefix_length_to' => 32,
    ]);

    $v6 = AddressBlock::factory()->ipv6()->create([
        'base_ip' => '2001:db8::',
        'gateway' => null,
        'prefix_length_from' => 48,
        'prefix_length_to' => 128,
    ]);

    $make = fn (AddressBlock $b, string $ip, AddressState $state) => Address::factory()->for($b)->create([
        'ip' => $ip,
        'prefix_length' => $b->prefix_length_to,
        'state' => $state,
    ]);

    $network = $make($v4, '192.0.2.0', AddressState::Reserved);
    $gateway = $make($v4, '192.0.2.1', AddressState::Reserved);
    $broadcast = $make($v4, '192.0.2.255', AddressState::Reserved);
    $hold = $make($v4, '192.0.2.50', AddressState::Reserved);
    $free = $make($v4, '192.0.2.51', AddressState::Available);

    $p2pBase = $make($p2p, '203.0.113.0', AddressState::Reserved);
    $p2pGateway = $make($p2p, '203.0.113.1', AddressState::Reserved);

    $anycast = $make($v6, '2001:db8::', AddressState::Reserved);
    $v6hold = $make($v6, '2001:db8::99', AddressState::Reserved);

    // Re-run the real migration over rows that predate the column.
    Schema::table('addresses', fn ($t) => $t->dropColumn('state_reason'));
    (require database_path('migrations/2026_07_24_000000_add_state_reason_to_addresses.php'))->up();

    $reason = fn (Address $a) => $a->refresh()->state_reason;

    expect($reason($network))->toBe(AddressStateReason::System)
        ->and($reason($gateway))->toBe(AddressStateReason::System)
        ->and($reason($broadcast))->toBe(AddressStateReason::System)
        ->and($reason($anycast))->toBe(AddressStateReason::System)
        // /31: base is allocatable, gateway is still structural.
        ->and($reason($p2pBase))->toBe(AddressStateReason::Admin)
        ->and($reason($p2pGateway))->toBe(AddressStateReason::System)
        // Operator holds and non-reserved rows.
        ->and($reason($hold))->toBe(AddressStateReason::Admin)
        ->and($reason($v6hold))->toBe(AddressStateReason::Admin)
        ->and($reason($free))->toBeNull();
});
