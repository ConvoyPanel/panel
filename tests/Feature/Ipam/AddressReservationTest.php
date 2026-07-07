<?php

use App\Enums\Network\AddressState;
use App\Enums\Network\AddressVersion;
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
    $this->block = AddressBlock::factory()->for($this->group)->create([
        'version' => AddressVersion::IPv4,
        'base_ip' => '192.0.2.0',
        'gateway' => null,
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);
});

function reserveUrl(Address $address): string
{
    return "/api/admin/address-block-groups/{$address->addressBlock->address_block_group_id}"
        . "/address-blocks/{$address->address_block_id}/addresses/{$address->id}/reserve";
}

it('reserves and unreserves an available address', function () {
    $address = Address::factory()->for($this->block)->create(['ip' => '192.0.2.50', 'state' => AddressState::Available]);

    $this->actingAs($this->admin)->postJson(reserveUrl($address))->assertSuccessful();
    expect($address->refresh()->state)->toBe(AddressState::Reserved);

    $this->actingAs($this->admin)->deleteJson(reserveUrl($address))->assertSuccessful();
    expect($address->refresh()->state)->toBe(AddressState::Available);
});

it('refuses to reserve an assigned address', function () {
    $server = Server::factory()->for(Node::factory()->for(Location::factory()))->for(Storage::factory())->create();
    $address = Address::factory()->for($this->block)->create([
        'ip' => '192.0.2.51',
        'server_id' => $server->id,
    ]);

    $this->actingAs($this->admin)->postJson(reserveUrl($address))
        ->assertStatus(409)
        ->assertJsonPath('code', 'address_not_available');

    expect($address->refresh()->state)->toBe(AddressState::Assigned);
});

it('refuses to unreserve an address that is not reserved', function () {
    $address = Address::factory()->for($this->block)->create(['ip' => '192.0.2.52', 'state' => AddressState::Available]);

    $this->actingAs($this->admin)->deleteJson(reserveUrl($address))
        ->assertStatus(409)
        ->assertJsonPath('code', 'address_not_reserved');
});

it('rejects assigning a reserved address to a server', function () {
    $server = Server::factory()->for(Node::factory()->for(Location::factory()))->for(Storage::factory())->create();
    $address = Address::factory()->for($this->block)->create(['ip' => '192.0.2.53', 'state' => AddressState::Reserved]);

    $this->actingAs($this->admin)
        ->patchJson("/api/admin/address-block-groups/{$this->group->id}/address-blocks/{$this->block->id}/addresses/{$address->id}", [
            'server_id' => $server->id,
        ])
        ->assertStatus(422);

    expect($address->refresh()->state)->toBe(AddressState::Reserved);
});

it('auto-reserves the network, broadcast and gateway addresses when generating a dense block', function () {
    $block = AddressBlock::factory()->for($this->group)->create([
        'version' => AddressVersion::IPv4,
        'base_ip' => '198.51.100.0',
        'gateway' => '198.51.100.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);

    app(\App\Actions\Ipam\GenerateAddressesAction::class)->execute($block);

    $reserved = Address::where('address_block_id', $block->id)
        ->where('state', AddressState::Reserved)
        ->pluck('ip')
        ->sort()
        ->values()
        ->all();

    expect($reserved)->toBe(['198.51.100.0', '198.51.100.1', '198.51.100.255']); // network, gateway, broadcast
});
