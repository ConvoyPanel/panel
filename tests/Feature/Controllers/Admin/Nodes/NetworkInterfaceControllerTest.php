<?php

use App\Models\AddressBlockGroup;
use App\Models\AddressBlockGroupToInterface;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use App\Models\Vlan;

beforeEach(function () {
    $this->user = User::factory()->create([
        'root_admin' => true,
    ]);
    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
    $this->interface = NetworkInterface::create([
        'node_id' => $this->node->id,
        'name' => 'vmbr0',
    ]);
});

it('counts the servers attached to each interface', function () {
    Server::factory()->count(2)->for($this->node)->create([
        'network_interface_id' => $this->interface->id,
    ]);
    // On the same node but on no bridge — must not be counted.
    Server::factory()->for($this->node)->create();

    $empty = NetworkInterface::create([
        'node_id' => $this->node->id,
        'name' => 'vmbr1',
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/network-interfaces",
    );

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('serversCount', 'id')->all())
        ->toBe([
            $this->interface->id => 2,
            $empty->id => 0,
        ]);
});

it('returns a zero count for a freshly created interface', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/admin/nodes/{$this->node->id}/network-interfaces",
        ['name' => 'vmbr2', 'is_vlan_aware' => false],
    );

    $response->assertCreated()->assertJsonPath('data.serversCount', 0);
});

it('lists declared and undeclared vlans together', function () {
    $trunk = NetworkInterface::factory()->for($this->node)->trunk(100)->create();
    Vlan::factory()->for($trunk, 'networkInterface')->create(['tag' => 205, 'name' => 'Customer A']);
    // Declared but empty — the case that made a fresh trunk look broken.
    Vlan::factory()->for($trunk, 'networkInterface')->create(['tag' => 400, 'name' => 'Reserved']);

    Server::factory()->for($this->node)->create([
        'network_interface_id' => $trunk->id,
        'vlan_tag' => 205,
    ]);
    // Inherits the bridge default, which nothing declared.
    Server::factory()->for($this->node)->create([
        'network_interface_id' => $trunk->id,
        'vlan_tag' => null,
    ]);
    // Carries a tag nobody wrote down.
    Server::factory()->for($this->node)->create([
        'network_interface_id' => $trunk->id,
        'vlan_tag' => 999,
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/network-interfaces",
    );

    $vlans = collect($response->assertOk()->json('data'))
        ->firstWhere('id', $trunk->id)['vlans'];

    expect(collect($vlans)->map(fn ($vlan) => [
        $vlan['tag'], $vlan['serversCount'], $vlan['id'] !== null,
    ])->all())->toBe([
        [100, 1, false],  // in use via inheritance, undeclared
        [205, 1, true],   // declared and in use
        [400, 0, true],   // declared, unused
        [999, 1, false],  // in use, undeclared
    ]);
});

it('reports no vlans for a bridge that does not trunk', function () {
    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/network-interfaces",
    );

    $response->assertOk()->assertJsonPath('data.0.vlans', []);
});

it('counts the address pools attached to an interface', function () {
    $pool = AddressBlockGroup::factory()->create();
    AddressBlockGroupToInterface::create([
        'address_block_group_id' => $pool->id,
        'network_interface_id' => $this->interface->id,
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/network-interfaces",
    );

    $response->assertOk()->assertJsonPath('data.0.addressPoolsCount', 1);
});

it('drops declared vlans when the bridge stops trunking', function () {
    $trunk = NetworkInterface::factory()->for($this->node)->trunk(100)->create();
    Vlan::factory()->for($trunk, 'networkInterface')->create(['tag' => 205]);

    $response = $this->actingAs($this->user)->putJson(
        "/api/admin/nodes/{$this->node->id}/network-interfaces/{$trunk->id}",
        ['name' => $trunk->name, 'is_vlan_aware' => false],
    );

    // Nothing can resolve to them any more — the sync forces a null tag on
    // every server here, which this endpoint also clears.
    $response->assertOk()->assertJsonPath('data.vlans', []);
    expect(Vlan::query()->count())->toBe(0);
});

/**
 * The client merges the update response straight into its cached list, so an
 * edit that omitted the count would blank out the servers already on the
 * bridge until the next refetch.
 */
it('keeps the count on the update response', function () {
    Server::factory()->count(3)->for($this->node)->create([
        'network_interface_id' => $this->interface->id,
    ]);

    $response = $this->actingAs($this->user)->putJson(
        "/api/admin/nodes/{$this->node->id}/network-interfaces/{$this->interface->id}",
        ['name' => 'vmbr0', 'description' => 'Primary bridge'],
    );

    $response->assertOk()->assertJsonPath('data.serversCount', 3);
});
