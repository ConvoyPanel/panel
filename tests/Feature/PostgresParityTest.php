<?php

/*
|--------------------------------------------------------------------------
| Behaviour MySQL gave for free
|--------------------------------------------------------------------------
|
| 5.x moved from MySQL to Postgres with the promise that nothing changes for
| the people using the panel. Each test here pins something MySQL did on its
| own (case-insensitive comparison, forgiving casts, NULL ordering, row order)
| that Postgres doesn't, and which the code now does explicitly.
|
*/

use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\AddressPoolToNode;
use Convoy\Models\Backup;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
});

it('signs in with the email typed in a different case', function () {
    $user = User::factory()->create(['email' => 'bob@example.com']);

    $this->post('/login', ['email' => 'Bob@Example.COM', 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
});

it('still refuses a wrong password for a differently cased email', function () {
    User::factory()->create(['email' => 'bob@example.com']);

    $this->post('/login', ['email' => 'BOB@example.com', 'password' => 'wrong']);

    $this->assertGuest();
});

it('refuses a second account whose email differs only in case', function () {
    User::factory()->create(['email' => 'bob@example.com']);

    $this->actingAs($this->admin)->postJson('/api/admin/users', [
        'name' => 'Bob Again',
        'email' => 'Bob@Example.com',
        'password' => 'Password1!',
        'root_admin' => false,
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('lets a user keep their own email when it is saved again', function () {
    $user = User::factory()->create(['email' => 'bob@example.com']);

    $this->actingAs($this->admin)->putJson("/api/admin/users/{$user->id}", [
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'root_admin' => false,
    ])->assertOk();
});

it('searches by text, ignoring case, without comparing the text to an id', function () {
    User::factory()->create(['name' => 'alice liddell']);

    $this->actingAs($this->admin)->getJson('/api/admin/users?filter[*]=ALICE')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'alice liddell');
});

it('finds an account by its exact email in any case', function () {
    User::factory()->create(['email' => 'carol@example.com']);

    $this->actingAs($this->admin)->getJson('/api/admin/users?filter[email]=Carol@Example.com')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('keeps a search inside the list it was made on', function () {
    $location = Location::factory()->create();
    [$nodeA, $nodeB] = Node::factory()->for($location)->count(2)->create();

    foreach ([$nodeA, $nodeB] as $node) {
        $pool = AddressPool::factory()->create();
        AddressPoolToNode::create(['address_pool_id' => $pool->id, 'node_id' => $node->id]);
        Address::factory()->create(['address_pool_id' => $pool->id, 'type' => 'ipv4', 'address' => $node->is($nodeA) ? '10.0.0.1' : '10.0.0.2']);
    }

    // Ungrouped, the search's OR escaped the node scope and found node B's address.
    $this->actingAs($this->admin)->getJson("/api/admin/nodes/{$nodeA->id}/addresses?filter[*]=10.0.0.2")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('answers a route id that is not a number with a 404', function () {
    $this->actingAs($this->admin)->getJson('/api/admin/nodes/not-a-number')->assertNotFound();
});

it('treats an id filter that is not a number as matching nothing', function () {
    Node::factory()->for(Location::factory()->create())->create();

    $this->actingAs($this->admin)->getJson('/api/admin/nodes?filter[location_id]=abc')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('validates a server id that is not a number instead of querying with it', function () {
    $pool = AddressPool::factory()->create();

    $this->actingAs($this->admin)->postJson("/api/admin/address-pools/{$pool->id}/addresses", [
        'type' => 'ipv4',
        'address' => '10.1.0.5',
        'cidr' => 24,
        'gateway' => '10.1.0.1',
        'server_id' => 'abc',
    ])->assertUnprocessable()->assertJsonValidationErrors('server_id');
});

it('returns a server\'s addresses in id order even after one is updated', function () {
    [, , $node, $server] = createServerModel();
    $pool = AddressPool::factory()->create();
    AddressPoolToNode::create(['address_pool_id' => $pool->id, 'node_id' => $node->id]);

    $first = Address::factory()->create(['address_pool_id' => $pool->id, 'server_id' => $server->id, 'type' => 'ipv4']);
    Address::factory()->create(['address_pool_id' => $pool->id, 'server_id' => $server->id, 'type' => 'ipv4']);

    // An UPDATE writes a new row version at the end of the table, so without an
    // ORDER BY Postgres would now return the second address first.
    $first->update(['gateway' => '10.9.9.9']);

    expect($server->addresses()->first()->id)->toBe($first->id);
});

it('lists unfinished backups first when sorting by completion ascending', function () {
    [$user, , , $server] = createServerModel();

    Backup::factory()->for($server)->create(['completed_at' => now()->subDay()]);
    $pending = Backup::factory()->for($server)->create(['completed_at' => null]);

    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/backups?sort=completed_at")
        ->assertOk()
        ->assertJsonPath('data.0.uuid', $pending->uuid);
});
