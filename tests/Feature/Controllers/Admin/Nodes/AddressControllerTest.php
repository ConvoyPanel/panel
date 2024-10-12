<?php

use App\Models\AddressPool;
use App\Models\AddressPoolToNode;
use App\Models\Location;
use App\Models\Node;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'root_admin' => true,
    ]);
    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
    $this->pool = AddressPool::factory()->create();
    AddressPoolToNode::create([
        'address_pool_id' => $this->pool->id,
        'node_id' => $this->node->id,
    ]);
});

it('can fetch addresses', function () {
    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/addresses",
    );

    $response->assertOk();
});
