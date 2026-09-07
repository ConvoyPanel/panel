<?php

use App\Enums\Network\AddressState;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
    $this->group = AddressBlockGroup::factory()->create();

    // A /24 handing out individual addresses: 256 units, of which .0, .255 and the gateway are
    // reserved by the panel itself.
    $this->block = AddressBlock::factory()->for($this->group)->create([
        'base_ip' => '192.0.2.0',
        'gateway' => '192.0.2.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);

    $server = Server::factory()
        ->for(Node::factory()->for(Location::factory()))
        ->for(Storage::factory())
        ->create();

    // 3 system, 2 operator-held, 4 assigned, 5 free — 14 rows of a 256-unit space.
    foreach (['192.0.2.0', '192.0.2.1', '192.0.2.255'] as $ip) {
        Address::factory()->for($this->block)->systemReserved()->create(['ip' => $ip]);
    }

    foreach (['192.0.2.2', '192.0.2.3'] as $ip) {
        Address::factory()->for($this->block)->create(['ip' => $ip, 'state' => AddressState::Reserved]);
    }

    foreach (['192.0.2.10', '192.0.2.11', '192.0.2.12', '192.0.2.13'] as $ip) {
        Address::factory()->for($this->block)->create(['ip' => $ip, 'server_id' => $server->id]);
    }

    foreach (['192.0.2.20', '192.0.2.21', '192.0.2.22', '192.0.2.88', '192.0.2.89'] as $ip) {
        Address::factory()->for($this->block)->create(['ip' => $ip, 'state' => AddressState::Available]);
    }
});

function blockIndexUrl(AddressBlockGroup $group): string
{
    return "/api/admin/address-block-groups/{$group->id}/address-blocks";
}

function addressIndexUrl(AddressBlock $block): string
{
    return "/api/admin/address-block-groups/{$block->address_block_group_id}"
        ."/address-blocks/{$block->id}/addresses";
}

it('reports a block’s capacity split by state', function () {
    $response = $this->actingAs($this->admin)
        ->getJson(blockIndexUrl($this->group))
        ->assertSuccessful();

    $capacity = $response->json('items.0.capacity');

    expect($capacity['totalUnits'])->toBe(256)
        ->and($capacity['isSparse'])->toBeFalse()
        ->and($capacity['generatedCount'])->toBe(14)
        ->and($capacity['assignedCount'])->toBe(4)
        ->and($capacity['reservedCount'])->toBe(2)
        ->and($capacity['systemCount'])->toBe(3)
        ->and($capacity['availableCount'])->toBe(5);
});

it('rolls a block’s capacity up onto its pool', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/admin/address-block-groups')
        ->assertSuccessful();

    $capacity = $response->json('items.0.capacity');

    expect($capacity['totalUnits'])->toBe(256)
        ->and($capacity['assignedCount'])->toBe(4)
        ->and($capacity['reservedCount'])->toBe(2)
        ->and($capacity['systemCount'])->toBe(3)
        ->and($capacity['availableCount'])->toBe(5);
});

it('reports no total for a sparse block rather than a made-up one', function () {
    // A /64 handing out /128s is 2^64 units — past a PHP int, and never materialized.
    $sparse = AddressBlock::factory()->for($this->group)->create([
        'base_ip' => '2001:db8::',
        'gateway' => null,
        'prefix_length_from' => 64,
        'prefix_length_to' => 128,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(blockIndexUrl($this->group).'?filter[base_ip]=2001:db8::')
        ->assertSuccessful();

    $capacity = $response->json('items.0.capacity');

    expect($sparse->isSparse())->toBeTrue()
        ->and($capacity['totalUnits'])->toBeNull()
        ->and($capacity['isSparse'])->toBeTrue();
});

it('measures a pool by its sized blocks and names the sparse ones', function () {
    // A sparse block beside a /24 must not erase the /24's answer — that is the thing the screen
    // was opened for. It is counted and named instead.
    $sparse = AddressBlock::factory()->for($this->group)->create([
        'base_ip' => '2001:db8::',
        'gateway' => null,
        'prefix_length_from' => 64,
        'prefix_length_to' => 128,
    ]);
    Address::factory()->for($sparse)->create([
        'ip' => '2001:db8::5',
        'prefix_length' => 128,
        'state' => AddressState::Reserved,
    ]);

    $capacity = $this->actingAs($this->admin)
        ->getJson('/api/admin/address-block-groups')
        ->assertSuccessful()
        ->json('items.0.capacity');

    expect($capacity['totalUnits'])->toBe(256)
        ->and($capacity['isSparse'])->toBeFalse()
        ->and($capacity['sparseBlockCount'])->toBe(1)
        // The sparse block's own address is not counted against the /24's denominator.
        ->and($capacity['reservedCount'])->toBe(2);
});

it('reports no pool total when every block is sparse', function () {
    $allSparse = AddressBlockGroup::factory()->create();
    AddressBlock::factory()->for($allSparse)->create([
        'base_ip' => '2001:db8:1::',
        'gateway' => null,
        'prefix_length_from' => 64,
        'prefix_length_to' => 128,
    ]);

    $capacity = $this->actingAs($this->admin)
        ->getJson('/api/admin/address-block-groups?filter[name]='.urlencode($allSparse->name))
        ->assertSuccessful()
        ->json('items.0.capacity');

    expect($capacity['totalUnits'])->toBeNull()
        ->and($capacity['isSparse'])->toBeTrue()
        ->and($capacity['sparseBlockCount'])->toBe(1);
});

it('filters addresses by the four states an operator sees', function () {
    $url = addressIndexUrl($this->block);

    $available = $this->actingAs($this->admin)->getJson($url.'?filter[state]=available')
        ->assertSuccessful()->json('items');
    expect($available)->toHaveCount(5);

    $assigned = $this->actingAs($this->admin)->getJson($url.'?filter[state]=assigned')
        ->assertSuccessful()->json('items');
    expect($assigned)->toHaveCount(4);

    // `reserved` means an operator hold, and must not sweep up the panel's own reservations.
    $reserved = $this->actingAs($this->admin)->getJson($url.'?filter[state]=reserved')
        ->assertSuccessful()->json('items');
    expect($reserved)->toHaveCount(2)
        ->and(collect($reserved)->pluck('ip')->sort()->values()->all())
        ->toBe(['192.0.2.2', '192.0.2.3']);

    $system = $this->actingAs($this->admin)->getJson($url.'?filter[state]=system')
        ->assertSuccessful()->json('items');
    expect($system)->toHaveCount(3);
});

it('accepts several states at once, the way a faceted filter sends them', function () {
    $items = $this->actingAs($this->admin)
        ->getJson(addressIndexUrl($this->block).'?filter[state][]=available&filter[state][]=assigned')
        ->assertSuccessful()
        ->json('items');

    expect($items)->toHaveCount(9);
});

it('matches nothing for a state token it does not know', function () {
    $items = $this->actingAs($this->admin)
        ->getJson(addressIndexUrl($this->block).'?filter[state]=bogus')
        ->assertSuccessful()
        ->json('items');

    expect($items)->toBeEmpty();
});

it('searches addresses by a partial ip, not just an exact one', function () {
    $items = $this->actingAs($this->admin)
        ->getJson(addressIndexUrl($this->block).'?filter[ip]=.8')
        ->assertSuccessful()
        ->json('items');

    expect(collect($items)->pluck('ip')->sort()->values()->all())
        ->toBe(['192.0.2.88', '192.0.2.89']);
});

it('summarises every pool, not just the page on screen', function () {
    $summary = $this->actingAs($this->admin)
        ->getJson('/api/admin/address-block-groups/summary')
        ->assertSuccessful()
        ->json('data');

    expect($summary['poolsCount'])->toBe(1)
        ->and($summary['blocksCount'])->toBe(1)
        ->and($summary['capacity']['totalUnits'])->toBe(256)
        ->and($summary['capacity']['assignedCount'])->toBe(4)
        ->and($summary['capacity']['availableCount'])->toBe(5)
        // 6 of 253 usable is nowhere near full.
        ->and($summary['blocksNearlyFull'])->toBe(0)
        ->and($summary['fullestBlock'])->toBeNull();
});

it('names the block that is about to fill up', function () {
    $full = AddressBlock::factory()->for($this->group)->create([
        'base_ip' => '198.51.100.0',
        'gateway' => null,
        'prefix_length_from' => 29,   // 8 units, none reserved by the panel (no gateway)
        'prefix_length_to' => 32,
    ]);

    foreach (range(0, 7) as $offset) {
        Address::factory()->for($full)->create([
            'ip' => "198.51.100.{$offset}",
            'state' => AddressState::Reserved,
        ]);
    }

    $summary = $this->actingAs($this->admin)
        ->getJson('/api/admin/address-block-groups/summary')
        ->assertSuccessful()
        ->json('data');

    expect($summary['blocksNearlyFull'])->toBe(1)
        ->and($summary['fullestBlock']['label'])->toBe('198.51.100.0/29')
        ->and($summary['fullestBlock']['percent'])->toEqual(100)
        ->and($summary['fullestBlock']['id'])->toBe($full->id);
});

it('does not call an ungenerated block nearly full', function () {
    AddressBlock::factory()->for($this->group)->create([
        'base_ip' => '198.51.100.0',
        'gateway' => null,
        'prefix_length_from' => 29,
        'prefix_length_to' => 32,
    ]);

    $summary = $this->actingAs($this->admin)
        ->getJson('/api/admin/address-block-groups/summary')
        ->assertSuccessful()
        ->json('data');

    expect($summary['blocksNearlyFull'])->toBe(0);
});
