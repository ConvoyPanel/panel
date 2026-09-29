<?php

use App\Enums\Network\AddressState;
use App\Enums\Server\MigrationDisposition;
use App\Enums\Server\MigrationTransport;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Cluster;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Models\User;
use App\Services\Servers\ServerMigrationService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->cluster = Cluster::factory()->create();
    $this->location = Location::factory()->create();
    $this->source = Node::factory()->for($this->location)
        ->create(['name' => 'pve1', 'cluster_id' => $this->cluster->id]);
    $this->target = Node::factory()->for($this->location)
        ->create(['name' => 'pve2', 'cluster_id' => $this->cluster->id]);

    $this->pool = AddressBlockGroup::factory()->create(['name' => 'Public /24']);
    $this->block = AddressBlock::factory()->for($this->pool, 'addressBlockGroup')
        ->create(['base_ip' => '192.0.2.0', 'gateway' => '192.0.2.1', 'prefix_length_from' => 24, 'prefix_length_to' => 32]);

    $this->sourceBridge = NetworkInterface::create(['node_id' => $this->source->id, 'name' => 'vmbr0']);
    $this->sourceBridge->addressBlockGroups()->attach($this->pool->id);

    $storage = Storage::factory()->create();
    $this->source->storages()->attach($storage);
    $this->target->storages()->attach($storage);

    $this->server = Server::factory()->create([
        'user_id' => User::factory(),
        'node_id' => $this->source->id,
        'network_interface_id' => $this->sourceBridge->id,
        'storage_id' => $storage->id,
        'vmid' => 150,
    ]);

    Address::factory()->for($this->block)->create([
        'ip' => '192.0.2.10',
        'server_id' => $this->server->id,
        'state' => AddressState::Assigned,
    ]);

    $this->service = app(ServerMigrationService::class);
});

it('preserves the addresses when the destination bridge carries the same pool', function () {
    fakeMigrationPreflight();

    $bridge = NetworkInterface::create(['node_id' => $this->target->id, 'name' => 'vmbr0']);
    $bridge->addressBlockGroups()->attach($this->pool->id);

    $plan = $this->service->plan($this->server);

    expect($plan->candidates)->toHaveCount(1)
        ->and($plan->candidates[0]->nodeName)->toBe('pve2')
        ->and($plan->candidates[0]->disposition)->toBe(MigrationDisposition::Preserve)
        ->and($plan->candidates[0]->bridgeName)->toBe('vmbr0');
});

it('blocks when the destination has a same-named bridge that is not attached to the pool', function () {
    // The topology model is incomplete rather than genuinely different, and PVE
    // treats the two vmbr0s as one network. Guessing between those is the
    // failure mode the whole design exists to avoid.
    fakeMigrationPreflight();

    NetworkInterface::create(['node_id' => $this->target->id, 'name' => 'vmbr0']);

    $plan = $this->service->plan($this->server);

    expect($plan->candidates[0]->disposition)->toBe(MigrationDisposition::Blocked)
        ->and($plan->candidates[0]->blockedReason)
        ->toContain('Public /24')
        ->toContain('vmbr0');
});

it('reallocates when the destination fronts genuinely different networks', function () {
    fakeMigrationPreflight();

    $otherPool = AddressBlockGroup::factory()->create(['name' => 'Rack B /24']);
    $otherBlock = AddressBlock::factory()->for($otherPool, 'addressBlockGroup')
        ->create(['base_ip' => '198.51.100.0', 'gateway' => '198.51.100.1', 'prefix_length_from' => 24, 'prefix_length_to' => 32]);
    Address::factory()->for($otherBlock)->create(['ip' => '198.51.100.20', 'server_id' => null]);

    // No vmbr0 on pve2 at all: the networks really are different.
    $bridge = NetworkInterface::create(['node_id' => $this->target->id, 'name' => 'vmbr1']);
    $bridge->addressBlockGroups()->attach($otherPool->id);

    $plan = $this->service->plan($this->server);

    expect($plan->candidates[0]->disposition)->toBe(MigrationDisposition::Reallocate)
        ->and($plan->candidates[0]->bridgeName)->toBe('vmbr1')
        // A new address only reaches the guest through a reboot, so an online
        // migration is never on offer here.
        ->and($plan->candidates[0]->canMigrateOnline)->toBeFalse();
});

it('shows the addresses the server would lose and gain before anything is committed', function () {
    fakeMigrationPreflight();

    $otherPool = AddressBlockGroup::factory()->create(['name' => 'Rack B /24']);
    $otherBlock = AddressBlock::factory()->for($otherPool, 'addressBlockGroup')
        ->create(['base_ip' => '198.51.100.0', 'gateway' => '198.51.100.1', 'prefix_length_from' => 24, 'prefix_length_to' => 32]);
    Address::factory()->for($otherBlock)->create(['ip' => '198.51.100.20', 'server_id' => null]);

    $bridge = NetworkInterface::create(['node_id' => $this->target->id, 'name' => 'vmbr1']);
    $bridge->addressBlockGroups()->attach($otherPool->id);

    $preview = $this->service->preview($this->server, $this->target);

    expect($preview->released)->toHaveCount(1)
        ->and($preview->released[0]->ip)->toBe('192.0.2.10')
        ->and($preview->allocated)->toHaveCount(1)
        ->and($preview->allocated[0]->ip)->toBe('198.51.100.20')
        ->and($preview->isShortOnAddresses)->toBeFalse();
});

it('leaves the previewed address free, having only looked at it', function () {
    // The preview runs the real allocator and rolls it back, so what it shows is
    // what a commit would hand out. It must not be a reservation.
    fakeMigrationPreflight();

    $otherPool = AddressBlockGroup::factory()->create(['name' => 'Rack B /24']);
    $otherBlock = AddressBlock::factory()->for($otherPool, 'addressBlockGroup')
        ->create(['base_ip' => '198.51.100.0', 'gateway' => '198.51.100.1', 'prefix_length_from' => 24, 'prefix_length_to' => 32]);
    $free = Address::factory()->for($otherBlock)->create(['ip' => '198.51.100.20', 'server_id' => null]);

    $bridge = NetworkInterface::create(['node_id' => $this->target->id, 'name' => 'vmbr1']);
    $bridge->addressBlockGroups()->attach($otherPool->id);

    $this->service->preview($this->server, $this->target);

    expect($free->fresh())->state->toBe(AddressState::Available)->server_id->toBeNull();
});

it('blocks a destination that has no bridge with free addresses', function () {
    fakeMigrationPreflight();

    // A bridge with no pool on it can carry nothing.
    NetworkInterface::create(['node_id' => $this->target->id, 'name' => 'vmbr1']);

    $plan = $this->service->plan($this->server);

    expect($plan->candidates[0]->disposition)->toBe(MigrationDisposition::Blocked)
        ->and($plan->candidates[0]->blockedReason)->toContain('enough free addresses');
});

it('passes on what Proxmox itself refuses rather than re-deriving it', function () {
    fakeMigrationPreflight([
        'allowed_nodes' => ['pve1'],
        'not_allowed_nodes' => ['pve2' => ['unavailable_storages' => ['local-lvm']]],
    ]);

    $bridge = NetworkInterface::create(['node_id' => $this->target->id, 'name' => 'vmbr0']);
    $bridge->addressBlockGroups()->attach($this->pool->id);

    $plan = $this->service->plan($this->server);

    expect($plan->candidates[0]->disposition)->toBe(MigrationDisposition::Blocked)
        ->and($plan->candidates[0]->blockedReason)->toContain('local-lvm');
});

it('blocks every destination while a device is passed through to the guest', function () {
    fakeMigrationPreflight(['local_resources' => ['hostpci0']]);

    $bridge = NetworkInterface::create(['node_id' => $this->target->id, 'name' => 'vmbr0']);
    $bridge->addressBlockGroups()->attach($this->pool->id);

    $plan = $this->service->plan($this->server);

    expect($plan->localResources)->toBe(['hostpci0'])
        ->and($plan->candidates[0]->disposition)->toBe(MigrationDisposition::Blocked)
        ->and($plan->candidates[0]->blockedReason)->toContain('hostpci0');
});

it('offers a standalone node the Anchor transport rather than nothing', function () {
    fakeMigrationPreflight();
    $standalone = Cluster::factory()->standalone()->create();
    $this->source->forceFill(['cluster_id' => $standalone->id])->save();

    $plan = $this->service->plan($this->server->fresh());

    // A node with no cluster used to have nowhere to go at all. It still has
    // no `qm migrate` destination, which is why the one candidate here is the
    // other transport rather than the same one relabelled.
    expect($plan->candidates)->toHaveCount(1)
        ->and($plan->candidates[0]->transport)->toBe(MigrationTransport::Anchor)
        ->and($plan->emptyReason)->toBeNull();
});

it('says so plainly when no other node is registered at all', function () {
    $this->target->delete();

    $plan = $this->service->plan($this->server->fresh());

    expect($plan->candidates)->toBeEmpty()
        ->and($plan->emptyReason)->toContain('No other node');

    // Nothing was asked of PVE: there was no question to ask.
    Http::assertNothingSent();
});
