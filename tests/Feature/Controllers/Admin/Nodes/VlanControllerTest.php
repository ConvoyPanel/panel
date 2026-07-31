<?php

use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use App\Models\Vlan;

beforeEach(function () {
    $this->user = User::factory()->create(['root_admin' => true]);
    $this->node = Node::factory()->create();
    $this->trunk = NetworkInterface::factory()->for($this->node)->trunk(100)->create();

    $this->url = "/api/admin/nodes/{$this->node->id}/network-interfaces/{$this->trunk->id}/vlans";
});

it('declares a vlan that no server uses yet', function () {
    $response = $this->actingAs($this->user)->postJson($this->url, [
        'tag' => 205,
        'name' => 'Customer A',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.tag', 205)
        ->assertJsonPath('data.name', 'Customer A')
        ->assertJsonPath('data.serversCount', 0);
});

it('adopts the servers already carrying a tag when it is declared', function () {
    Server::factory()->count(2)->for($this->node)->create([
        'network_interface_id' => $this->trunk->id,
        'vlan_tag' => 205,
    ]);

    $response = $this->actingAs($this->user)->postJson($this->url, [
        'tag' => 205,
        'name' => 'Customer A',
    ]);

    $response->assertCreated()->assertJsonPath('data.serversCount', 2);
});

it('rejects a duplicate tag on the same interface', function () {
    Vlan::factory()->for($this->trunk, 'networkInterface')->create(['tag' => 205]);

    $this->actingAs($this->user)
        ->postJson($this->url, ['tag' => 205])
        ->assertJsonValidationErrorFor('tag');
});

it('allows the same tag on a different interface', function () {
    Vlan::factory()->for($this->trunk, 'networkInterface')->create(['tag' => 205]);

    $other = NetworkInterface::factory()->for($this->node)->trunk()->create();

    $this->actingAs($this->user)
        ->postJson(
            "/api/admin/nodes/{$this->node->id}/network-interfaces/{$other->id}/vlans",
            ['tag' => 205],
        )
        ->assertCreated();
});

it('refuses to declare a vlan on a bridge that does not trunk', function () {
    $plain = NetworkInterface::factory()->for($this->node)->create();

    $this->actingAs($this->user)
        ->postJson(
            "/api/admin/nodes/{$this->node->id}/network-interfaces/{$plain->id}/vlans",
            ['tag' => 205],
        )
        ->assertJsonValidationErrorFor('tag');
});

it('cannot reach a vlan through another interface', function () {
    $vlan = Vlan::factory()->for($this->trunk, 'networkInterface')->create(['tag' => 205]);
    $other = NetworkInterface::factory()->for($this->node)->trunk()->create();

    $this->actingAs($this->user)
        ->deleteJson(
            "/api/admin/nodes/{$this->node->id}/network-interfaces/{$other->id}/vlans/{$vlan->id}",
        )
        ->assertNotFound();

    expect(Vlan::query()->whereKey($vlan->id)->exists())->toBeTrue();
});

it('leaves server tags alone when a declaration is deleted', function () {
    $vlan = Vlan::factory()->for($this->trunk, 'networkInterface')->create(['tag' => 205]);
    $server = Server::factory()->for($this->node)->create([
        'network_interface_id' => $this->trunk->id,
        'vlan_tag' => 205,
    ]);

    $this->actingAs($this->user)
        ->deleteJson("{$this->url}/{$vlan->id}")
        ->assertNoContent();

    // The tag is still what Proxmox is told; the VLAN simply goes back to
    // being undeclared.
    expect($server->refresh()->vlan_tag)->toBe(205);
});
