<?php

use App\Models\AddressBlockGroup;
use App\Models\AddressBlockGroupToInterface;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\User;

it('can filter address block groups by node', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $location = Location::factory()->create();
    $node = Node::factory()->for($location)->create();
    $otherNode = Node::factory()->for($location)->create();

    $matchingGroup = AddressBlockGroup::factory()->create(['name' => 'matching']);
    $otherGroup = AddressBlockGroup::factory()->create(['name' => 'other']);
    $unattachedGroup = AddressBlockGroup::factory()->create(['name' => 'unattached']);

    $interface = NetworkInterface::create([
        'node_id' => $node->id,
        'name' => 'vmbr0',
    ]);
    $otherInterface = NetworkInterface::create([
        'node_id' => $otherNode->id,
        'name' => 'vmbr1',
    ]);

    AddressBlockGroupToInterface::create([
        'address_block_group_id' => $matchingGroup->id,
        'network_interface_id' => $interface->id,
    ]);
    AddressBlockGroupToInterface::create([
        'address_block_group_id' => $otherGroup->id,
        'network_interface_id' => $otherInterface->id,
    ]);

    $response = $this->actingAs($admin)->getJson(
        "/api/admin/address-block-groups?filter[node_id]={$node->id}",
    );

    $response
        ->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('items.0.id', $matchingGroup->id)
        ->assertJsonMissing(['id' => $otherGroup->id])
        ->assertJsonMissing(['id' => $unattachedGroup->id]);
});
