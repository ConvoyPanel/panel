<?php

use App\Actions\Ipam\GenerateAddressesAction;
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
});

function addressMapUrl(AddressBlock $block): string
{
    return "/api/admin/address-block-groups/{$block->address_block_group_id}"
        ."/address-blocks/{$block->id}/addresses/map";
}

function mapTestBlock(AddressBlockGroup $group, array $attributes = []): AddressBlock
{
    return AddressBlock::factory()->for($group)->create(array_merge([
        'base_ip' => '192.0.2.0',
        'gateway' => '192.0.2.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ], $attributes));
}

it('returns one cell per unit, in address order', function () {
    $block = mapTestBlock($this->group);
    app(GenerateAddressesAction::class)->execute($block);

    $map = $this->actingAs($this->admin)
        ->getJson(addressMapUrl($block))
        ->assertSuccessful()
        ->json();

    expect($map['sparse'])->toBeFalse()
        ->and($map['tooLarge'])->toBeFalse()
        ->and($map['totalUnits'])->toBe(256)
        ->and($map['units'])->toHaveCount(256);

    // .0 network, .1 gateway and .255 broadcast are the panel's own, and they sit exactly where
    // the subnet puts them rather than wherever the rows happened to be written.
    expect($map['units'][0]['state'])->toBe('system')
        ->and($map['units'][0]['ip'])->toBe('192.0.2.0')
        ->and($map['units'][1]['state'])->toBe('system')
        ->and($map['units'][255]['state'])->toBe('system')
        ->and($map['units'][255]['ip'])->toBe('192.0.2.255')
        ->and($map['units'][100]['state'])->toBe('available')
        ->and($map['units'][100]['ip'])->toBe('192.0.2.100');
});

it('places an address by its address, not by its row order', function () {
    $block = mapTestBlock($this->group);

    // Written out of order on purpose: a map built from row order would put .200 at index 0.
    Address::factory()->for($block)->create(['ip' => '192.0.2.200', 'state' => AddressState::Available]);
    Address::factory()->for($block)->create(['ip' => '192.0.2.5', 'state' => AddressState::Reserved]);

    $map = $this->actingAs($this->admin)
        ->getJson(addressMapUrl($block))
        ->assertSuccessful()
        ->json();

    expect($map['units'][200]['ip'])->toBe('192.0.2.200')
        ->and($map['units'][200]['state'])->toBe('available')
        ->and($map['units'][5]['ip'])->toBe('192.0.2.5')
        ->and($map['units'][5]['state'])->toBe('reserved')
        // Everything else is a real unit with no record behind it yet — it still knows its own
        // address, because a cell labelled only by its offset tells an operator nothing.
        ->and($map['units'][0]['state'])->toBe('ungenerated')
        ->and($map['units'][0]['ip'])->toBe('192.0.2.0')
        ->and($map['units'][0]['addressId'])->toBeNull()
        ->and($map['units'][32]['ip'])->toBe('192.0.2.32')
        ->and($map['units'][32]['addressId'])->toBeNull();
});

it('names the server on an assigned cell', function () {
    $block = mapTestBlock($this->group);
    $server = Server::factory()
        ->for(Node::factory()->for(Location::factory()))
        ->for(Storage::factory())
        ->create(['name' => 'web-fra-01']);

    Address::factory()->for($block)->create([
        'ip' => '192.0.2.42',
        'server_id' => $server->id,
    ]);

    $unit = $this->actingAs($this->admin)
        ->getJson(addressMapUrl($block))
        ->assertSuccessful()
        ->json('units.42');

    expect($unit['state'])->toBe('assigned')
        ->and($unit['serverName'])->toBe('web-fra-01');
});

it('refuses to draw a sparse block rather than returning an empty grid', function () {
    $block = mapTestBlock($this->group, [
        'base_ip' => '2001:db8::',
        'gateway' => null,
        'prefix_length_from' => 64,
        'prefix_length_to' => 128,
    ]);

    $map = $this->actingAs($this->admin)
        ->getJson(addressMapUrl($block))
        ->assertSuccessful()
        ->json();

    expect($map['sparse'])->toBeTrue()
        ->and($map['totalUnits'])->toBeNull()
        ->and($map['units'])->toBeEmpty();
});

it('says a block is too large to draw instead of sending thousands of cells', function () {
    // A /16 of /32s is 65,536 units — materialized, but far past what a grid can show.
    $block = mapTestBlock($this->group, [
        'base_ip' => '10.0.0.0',
        'gateway' => null,
        'prefix_length_from' => 16,
        'prefix_length_to' => 32,
    ]);

    $map = $this->actingAs($this->admin)
        ->getJson(addressMapUrl($block))
        ->assertSuccessful()
        ->json();

    expect($map['sparse'])->toBeFalse()
        ->and($map['tooLarge'])->toBeTrue()
        ->and($map['totalUnits'])->toBe(65536)
        ->and($map['units'])->toBeEmpty();
});

it('draws a block that delegates sub-blocks, one cell per delegated prefix', function () {
    // A /24 handed out as /28s is 16 units, each a routed prefix rather than one address.
    $block = mapTestBlock($this->group, [
        'gateway' => null,
        'prefix_length_from' => 24,
        'prefix_length_to' => 28,
    ]);
    app(GenerateAddressesAction::class)->execute($block);

    $map = $this->actingAs($this->admin)
        ->getJson(addressMapUrl($block))
        ->assertSuccessful()
        ->json();

    expect($map['totalUnits'])->toBe(16)
        ->and($map['units'])->toHaveCount(16)
        ->and($map['units'][0]['ip'])->toBe('192.0.2.0')
        ->and($map['units'][1]['ip'])->toBe('192.0.2.16')
        ->and($map['units'][15]['ip'])->toBe('192.0.2.240');
});

it('gives an ungenerated sub-block its own prefix, not the parent\'s', function () {
    // /24 handed out as /28s: nothing generated, so every cell is a bare unit — and each one still
    // has to report the address of the prefix it stands for.
    $block = mapTestBlock($this->group, [
        'gateway' => null,
        'prefix_length_from' => 24,
        'prefix_length_to' => 28,
    ]);

    $units = $this->actingAs($this->admin)
        ->getJson(addressMapUrl($block))
        ->assertSuccessful()
        ->json('units');

    expect($units)->toHaveCount(16)
        ->and(collect($units)->pluck('state')->unique()->all())->toBe(['ungenerated'])
        ->and($units[0]['ip'])->toBe('192.0.2.0')
        ->and($units[1]['ip'])->toBe('192.0.2.16')
        ->and($units[15]['ip'])->toBe('192.0.2.240');
});

it('addresses an ungenerated v6 unit without overflowing an int', function () {
    $block = mapTestBlock($this->group, [
        'base_ip' => '2001:db8::',
        'gateway' => null,
        'prefix_length_from' => 120,
        'prefix_length_to' => 128,
    ]);

    $units = $this->actingAs($this->admin)
        ->getJson(addressMapUrl($block))
        ->assertSuccessful()
        ->json('units');

    expect($units)->toHaveCount(256)
        ->and($units[0]['ip'])->toBe('2001:db8::')
        ->and($units[255]['ip'])->toBe('2001:db8::ff');
});
