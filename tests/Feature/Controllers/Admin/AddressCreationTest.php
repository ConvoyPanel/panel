<?php

use Convoy\Jobs\Server\SyncNetworkSettings;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
    $this->pool = AddressPool::factory()->create();
});

it('creates a range of addresses without a server or MAC address', function () {
    $this->actingAs($this->admin)->postJson("/api/admin/address-pools/{$this->pool->id}/addresses", [
        'is_bulk_action' => true,
        'type' => 'ipv4',
        'starting_address' => '10.0.0.10',
        'ending_address' => '10.0.0.12',
        'cidr' => 24,
        'gateway' => '10.0.0.1',
    ])->assertNoContent();

    expect(Address::where('address_pool_id', $this->pool->id)->pluck('address')->sort()->values()->all())
        ->toBe(['10.0.0.10', '10.0.0.11', '10.0.0.12']);
    Queue::assertNotPushed(SyncNetworkSettings::class);
});

it('creates one address without a server or MAC address', function () {
    $this->actingAs($this->admin)->postJson("/api/admin/address-pools/{$this->pool->id}/addresses", [
        'is_bulk_action' => false,
        'type' => 'ipv4',
        'address' => '10.0.0.20',
        'cidr' => 24,
        'gateway' => '10.0.0.1',
    ])->assertOk()->assertJsonPath('data.address', '10.0.0.20');
});

it('syncs the server a range of addresses is assigned to', function () {
    [$_, $_, $_, $server] = createServerModel();

    $this->actingAs($this->admin)->postJson("/api/admin/address-pools/{$this->pool->id}/addresses", [
        'is_bulk_action' => true,
        'type' => 'ipv4',
        'starting_address' => '10.0.0.30',
        'ending_address' => '10.0.0.31',
        'cidr' => 24,
        'gateway' => '10.0.0.1',
        'server_id' => $server->id,
    ])->assertNoContent();

    Queue::assertPushed(SyncNetworkSettings::class);
});

it('stores an address, gateway and MAC address in lowercase', function () {
    $this->actingAs($this->admin)->postJson("/api/admin/address-pools/{$this->pool->id}/addresses", [
        'is_bulk_action' => false,
        'type' => 'ipv6',
        'address' => '2001:DB8::10',
        'cidr' => 64,
        'gateway' => '2001:DB8::1',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
    ])->assertOk();

    expect(Address::where('address_pool_id', $this->pool->id)->sole())
        ->address->toBe('2001:db8::10')
        ->gateway->toBe('2001:db8::1')
        ->mac_address->toBe('aa:bb:cc:dd:ee:ff');
});

it('rejects an address that differs from an existing one only in case', function () {
    Address::factory()->create([
        'address_pool_id' => $this->pool->id,
        'type' => 'ipv6',
        'address' => '2001:db8::10',
        'cidr' => 64,
        'gateway' => '2001:db8::1',
    ]);

    $this->actingAs($this->admin)->postJson("/api/admin/address-pools/{$this->pool->id}/addresses", [
        'is_bulk_action' => false,
        'type' => 'ipv6',
        'address' => '2001:DB8::10',
        'cidr' => 64,
        'gateway' => '2001:db8::1',
    ])->assertUnprocessable()->assertJsonValidationErrors(['address']);
});

it('stores a range of addresses and its gateway in lowercase', function () {
    $this->actingAs($this->admin)->postJson("/api/admin/address-pools/{$this->pool->id}/addresses", [
        'is_bulk_action' => true,
        'type' => 'ipv6',
        'starting_address' => '2001:DB8::A',
        'ending_address' => '2001:DB8::B',
        'cidr' => 64,
        'gateway' => '2001:DB8::1',
    ])->assertNoContent();

    expect(Address::where('address_pool_id', $this->pool->id)->orderBy('address')->get(['address', 'gateway'])->toArray())
        ->toBe([
            ['address' => '2001:db8::a', 'gateway' => '2001:db8::1'],
            ['address' => '2001:db8::b', 'gateway' => '2001:db8::1'],
        ]);
});

it('stores an edited address in lowercase', function () {
    $address = Address::factory()->create([
        'address_pool_id' => $this->pool->id,
        'type' => 'ipv6',
        'address' => '2001:db8::10',
        'cidr' => 64,
        'gateway' => '2001:db8::1',
    ]);

    $this->actingAs($this->admin)->putJson("/api/admin/address-pools/{$this->pool->id}/addresses/{$address->id}", [
        'type' => 'ipv6',
        'address' => '2001:DB8::20',
        'cidr' => 64,
        'gateway' => '2001:DB8::1',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'server_id' => null,
    ])->assertOk();

    expect($address->fresh())
        ->address->toBe('2001:db8::20')
        ->gateway->toBe('2001:db8::1')
        ->mac_address->toBe('aa:bb:cc:dd:ee:ff');
});
