<?php

use App\Models\AddressBlockGroup;
use App\Models\AddressBlockGroupToInterface;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'root_admin' => true,
    ]);
    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
    $this->pool = AddressBlockGroup::factory()->create();
    $interface = NetworkInterface::create([
        'node_id' => $this->node->id,
        'name' => 'vmbr0',
    ]);
    AddressBlockGroupToInterface::create([
        'address_block_group_id' => $this->pool->id,
        'network_interface_id' => $interface->id,
    ]);
});

it('can fetch addresses', function () {
    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/addresses",
    );

    $response->assertOk();
});
