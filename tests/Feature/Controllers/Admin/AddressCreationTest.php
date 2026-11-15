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
