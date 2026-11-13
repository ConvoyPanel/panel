<?php

use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Services\Addresses\AddressReachabilityService;

beforeEach(function () {
    $this->node = Node::factory()->for(Location::factory())->create();
    $this->pool = AddressBlockGroup::factory()->create(['name' => 'Public /24']);
    $this->block = AddressBlock::factory()->for($this->pool, 'addressBlockGroup')->create();
    $this->address = Address::factory()->for($this->block)->create();

    $this->service = app(AddressReachabilityService::class);
});

it('reaches an address through any interface on the node carrying its pool', function () {
    NetworkInterface::factory()->create(['node_id' => $this->node->id, 'name' => 'vmbr9'])
        ->addressBlockGroups()->attach($this->pool->id);

    expect($this->service->isReachable($this->address, $this->node))->toBeTrue();
});

it('does not reach an address whose pool is on no interface of the node', function () {
    NetworkInterface::factory()->create(['node_id' => $this->node->id, 'name' => 'vmbr0']);

    $elsewhere = Node::factory()->for(Location::factory())->create();
    NetworkInterface::factory()->create(['node_id' => $elsewhere->id])
        ->addressBlockGroups()->attach($this->pool->id);

    expect($this->service->isReachable($this->address, $this->node))->toBeFalse();
});

it('asks about one specific bridge, not the whole node', function () {
    $carrying = NetworkInterface::factory()->create(['node_id' => $this->node->id, 'name' => 'vmbr1']);
    $carrying->addressBlockGroups()->attach($this->pool->id);
    $bare = NetworkInterface::factory()->create(['node_id' => $this->node->id, 'name' => 'vmbr0']);

    expect($this->service->isReachable($this->address, $this->node))->toBeTrue()
        ->and($this->service->isReachableVia($this->address, $carrying->id))->toBeTrue()
        ->and($this->service->isReachableVia($this->address, $bare->id))->toBeFalse();
});

it('lists the blocks a node can route and leaves the rest out', function () {
    NetworkInterface::factory()->create(['node_id' => $this->node->id])
        ->addressBlockGroups()->attach($this->pool->id);

    $unreachable = AddressBlock::factory()->create();

    $blocks = $this->service->blocksReachableFrom($this->node);

    expect($blocks->pluck('id')->all())->toBe([$this->block->id])
        ->and($blocks->pluck('id'))->not->toContain($unreachable->id);
});

it('reports the pools a destination bridge would strand', function () {
    $bridge = NetworkInterface::factory()->create(['node_id' => $this->node->id, 'name' => 'vmbr0']);
    $server = Server::factory()->create(['node_id' => $this->node->id]);
    $this->address->forceFill(['server_id' => $server->id])->save();

    expect($this->service->unreachableGroupsFor($server, $bridge)->pluck('name')->all())
        ->toBe(['Public /24']);

    $bridge->addressBlockGroups()->attach($this->pool->id);

    expect($this->service->unreachableGroupsFor($server->fresh(), $bridge))->toBeEmpty();
});

it('strands nothing for a server that holds no addresses', function () {
    $bridge = NetworkInterface::factory()->create(['node_id' => $this->node->id, 'name' => 'vmbr0']);
    $server = Server::factory()->create(['node_id' => $this->node->id]);

    expect($this->service->unreachableGroupsFor($server, $bridge))->toBeEmpty()
        ->and($this->service->unreachableGroupsFor($server, null))->toBeEmpty();
});

it('narrows candidate bridges to those carrying every pool the server holds', function () {
    $second = AddressBlockGroup::factory()->create(['name' => 'Private /16']);
    $secondBlock = AddressBlock::factory()->for($second, 'addressBlockGroup')->create();

    $server = Server::factory()->create(['node_id' => $this->node->id]);
    $this->address->forceFill(['server_id' => $server->id])->save();
    Address::factory()->for($secondBlock)->create(['server_id' => $server->id]);

    $partial = NetworkInterface::factory()->create(['node_id' => $this->node->id, 'name' => 'vmbr0']);
    $partial->addressBlockGroups()->attach($this->pool->id);

    $full = NetworkInterface::factory()->create(['node_id' => $this->node->id, 'name' => 'vmbr1']);
    $full->addressBlockGroups()->attach([$this->pool->id, $second->id]);

    expect($this->service->interfacesCarrying($server, $this->node)->pluck('id')->all())
        ->toBe([$full->id]);
});
