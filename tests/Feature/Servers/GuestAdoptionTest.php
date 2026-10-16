<?php

use App\Actions\Server\AdoptGuestAction;
use App\Enums\Network\AddressOrigin;
use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Server\AddressAdoptionVerdict;
use App\Exceptions\Service\Server\AdoptionRefusedException;
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
use App\Services\Servers\GuestAdoptionService;
use Illuminate\Support\Facades\Http;

/** One `type=qemu` row as `/cluster/resources` reports it. */
function clusterGuest(int $vmid, string $node, array $extra = []): array
{
    return array_merge([
        'type' => 'qemu',
        'id' => "qemu/{$vmid}",
        'name' => "vm-{$vmid}",
        'status' => 'running',
        'vmid' => $vmid,
        'node' => $node,
        'maxcpu' => 2,
        'maxmem' => 2147483648,
        'maxdisk' => 10737418240,
        'uptime' => 3600,
    ], $extra);
}

/** Fake both reads adoption makes: the guest list and one guest's config. */
function fakeAdoptionReads(array $guests, array $configExtra = []): void
{
    Http::fake([
        '*/cluster/resources' => Http::response(['data' => $guests], 200),
        '*/config' => Http::response(serverConfigFixture($configExtra), 200),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);
}

beforeEach(function () {
    $this->cluster = Cluster::factory()->create();
    $this->node = Node::factory()->for(Location::factory())
        ->create(['name' => 'pve1', 'cluster_id' => $this->cluster->id]);

    $this->storage = Storage::factory()->create(['name' => 'local-lvm', 'cluster_id' => $this->cluster->id]);
    $this->node->storages()->attach($this->storage);

    // The fixture's net0 is on vmbr1 with ipconfig0 = 1.1.1.2/24.
    $this->pool = AddressBlockGroup::factory()->create(['name' => 'Public /24']);
    $this->bridge = NetworkInterface::create(['node_id' => $this->node->id, 'name' => 'vmbr1']);
    $this->bridge->addressBlockGroups()->attach($this->pool->id);
    $this->block = AddressBlock::factory()->for($this->pool, 'addressBlockGroup')->create([
        'name' => 'Public',
        'base_ip' => '1.1.1.0',
        'gateway' => '1.1.1.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);

    $this->owner = User::factory()->create();
    $this->service = app(GuestAdoptionService::class);
    $this->action = app(AdoptGuestAction::class);
});

it('offers guests the panel does not own and hides the ones it does', function () {
    Server::factory()->create([
        'user_id' => $this->owner->id,
        'node_id' => $this->node->id,
        'storage_id' => $this->storage->id,
        'vmid' => 100,
    ]);

    fakeAdoptionReads([clusterGuest(100, 'pve1'), clusterGuest(101, 'pve1')]);

    $list = $this->service->adoptable();

    expect($list->guests)->toHaveCount(1)
        ->and($list->guests[0]->vmid)->toBe(101)
        ->and($list->guests[0]->nodeName)->toBe('pve1')
        ->and($list->unreachable)->toBeEmpty();
});

it('never offers a template for adoption', function () {
    // A template is managed as an image, and adopting one makes a server row
    // nothing can ever start.
    fakeAdoptionReads([clusterGuest(101, 'pve1', ['template' => 1])]);

    expect($this->service->adoptable()->guests)->toBeEmpty();
});

it('does not warn about a cluster whose second member answered', function () {
    // Saying the list may be incomplete when it is not teaches an operator to
    // ignore the warning, which is the one thing it must never be.
    $second = Node::factory()->for(Location::factory())
        ->create(['name' => 'pve0', 'cluster_id' => $this->cluster->id]);

    // Ordered by name, so pve0 is asked first and fails; pve1 answers for the
    // same cluster.
    Http::fake([
        "https://{$second->fqdn}*" => Http::response(['message' => 'down'], 500),
        '*/cluster/resources' => Http::response(
            ['data' => [clusterGuest(101, 'pve1')]],
            200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    $list = $this->service->adoptable();

    expect($list->unreachable)->toBeEmpty()
        ->and($list->guests)->toHaveCount(1);
});

it('says which node it could not ask rather than reporting an empty list', function () {
    Http::fake(['*' => Http::response(['errors' => [['message' => 'connection refused']]], 500)]);

    $list = $this->service->adoptable();

    expect($list->guests)->toBeEmpty()
        ->and($list->unreachable)->toHaveCount(1)
        ->and($list->unreachable[0]->nodeName)->toBe('pve1');
});

it('offers to mint an address that falls in a reachable block with no row yet', function () {
    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    $preview = $this->service->preview($this->node, 101);

    expect($preview->addresses)->toHaveCount(1)
        ->and($preview->addresses[0]->ip)->toBe('1.1.1.2')
        ->and($preview->addresses[0]->verdict)->toBe(AddressAdoptionVerdict::Mint)
        ->and($preview->bridge)->toBe('vmbr1')
        ->and($preview->networkInterfaceId)->toBe($this->bridge->id)
        ->and($preview->storageName)->toBe('local-lvm')
        ->and($preview->storageId)->toBe($this->storage->id)
        ->and($preview->blockedReason)->toBeNull();
});

it('claims a free row that already exists for the observed address', function () {
    Address::factory()->for($this->block)->create(['ip' => '1.1.1.2', 'server_id' => null]);
    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    expect($this->service->preview($this->node, 101)->addresses[0]->verdict)
        ->toBe(AddressAdoptionVerdict::Claim);
});

it('refuses to steal an address another server already holds', function () {
    $other = Server::factory()->create([
        'user_id' => $this->owner->id,
        'node_id' => $this->node->id,
        'storage_id' => $this->storage->id,
        'vmid' => 200,
        'name' => 'already-here',
    ]);
    Address::factory()->for($this->block)->create([
        'ip' => '1.1.1.2',
        'server_id' => $other->id,
        'state' => AddressState::Assigned,
    ]);

    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    $candidate = $this->service->preview($this->node, 101)->addresses[0];

    expect($candidate->verdict)->toBe(AddressAdoptionVerdict::Conflict)
        ->and($candidate->conflictingServerName)->toBe('already-here');
});

it('surfaces a guest sitting on a system-reserved address instead of papering over it', function () {
    Address::factory()->for($this->block)->systemReserved()->create(['ip' => '1.1.1.2']);
    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    expect($this->service->preview($this->node, 101)->addresses[0]->verdict)
        ->toBe(AddressAdoptionVerdict::SystemReserved);
});

it('leaves an operator hold alone and says how to claim it', function () {
    Address::factory()->for($this->block)->create([
        'ip' => '1.1.1.2',
        'state' => AddressState::Reserved,
        'state_reason' => AddressStateReason::Admin,
    ]);
    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    $candidate = $this->service->preview($this->node, 101)->addresses[0];

    expect($candidate->verdict)->toBe(AddressAdoptionVerdict::AdminReserved)
        ->and($candidate->reason)->toContain('Unreserve');
});

it('will not claim an address out of a block this node cannot reach', function () {
    // Same block, but the pool is no longer on any bridge of this node.
    $this->bridge->addressBlockGroups()->detach();
    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    $candidate = $this->service->preview($this->node, 101)->addresses[0];

    expect($candidate->verdict)->toBe(AddressAdoptionVerdict::Unreachable)
        ->and($candidate->reason)->toContain('pve1');
});

it('records an address outside every block as unmanaged rather than inventing a block', function () {
    fakeAdoptionReads([clusterGuest(101, 'pve1')], ['ipconfig0' => 'ip=203.0.113.9/24,gw=203.0.113.1']);

    $candidate = $this->service->preview($this->node, 101)->addresses[0];

    expect($candidate->verdict)->toBe(AddressAdoptionVerdict::Unmanaged)
        ->and(AddressBlock::query()->count())->toBe(1);
});

it('refuses to pick between overlapping blocks', function () {
    $second = AddressBlockGroup::factory()->create(['name' => 'Also Public']);
    $this->bridge->addressBlockGroups()->attach($second->id);
    AddressBlock::factory()->for($second, 'addressBlockGroup')->create([
        'name' => 'Overlapping',
        'base_ip' => '1.1.0.0',
        'gateway' => '1.1.0.1',
        'prefix_length_from' => 16,
        'prefix_length_to' => 32,
    ]);

    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    expect($this->service->preview($this->node, 101)->addresses[0]->verdict)
        ->toBe(AddressAdoptionVerdict::Ambiguous);
});

it('treats a dhcp guest as one the panel does not configure', function () {
    fakeAdoptionReads([clusterGuest(101, 'pve1')], ['ipconfig0' => 'ip=dhcp']);

    $preview = $this->service->preview($this->node, 101);

    expect($preview->addresses)->toBeEmpty()
        ->and($preview->hasUnmanagedIpConfig)->toBeTrue();
});

it('names the NICs it read and did not reconcile', function () {
    fakeAdoptionReads([clusterGuest(101, 'pve1')], ['net1' => 'virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr9']);

    expect($this->service->preview($this->node, 101)->ignoredInterfaces)->toBe(['net1']);
});

it('adopts the guest, writing only to the database', function () {
    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    $server = $this->action->execute($this->node, 101, ['user_id' => $this->owner->id]);

    expect($server->vmid)->toBe(101)
        ->and($server->node_id)->toBe($this->node->id)
        ->and($server->network_interface_id)->toBe($this->bridge->id)
        ->and($server->storage_id)->toBe($this->storage->id)
        ->and($server->cpu)->toBe(2)
        ->and($server->flagged_at)->toBeNull()
        // Nothing installs it: it is already built.
        ->and($server->lifecycle->value)->toBe('ready')
        // Not stamped, because stamping smbios1 is a write to the guest.
        ->and($server->smbios_uuid)->toBeNull();

    $address = $server->addresses()->sole();

    expect($address->ip)->toBe('1.1.1.2')
        ->and($address->state)->toBe(AddressState::Assigned)
        ->and($address->origin)->toBe(AddressOrigin::Imported)
        ->and($address->observed_at)->not->toBeNull()
        ->and($server->primary_ipv4_address_id)->toBe($address->id);

    // The one thing that must never happen: a write to the guest.
    Http::assertNotSent(fn ($request) => $request->method() !== 'GET');
});

it('adopts a guest with an unresolvable address anyway, flagged', function () {
    $other = Server::factory()->create([
        'user_id' => $this->owner->id,
        'node_id' => $this->node->id,
        'storage_id' => $this->storage->id,
        'vmid' => 200,
        'name' => 'already-here',
    ]);
    Address::factory()->for($this->block)->create([
        'ip' => '1.1.1.2',
        'server_id' => $other->id,
        'state' => AddressState::Assigned,
    ]);

    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    $server = $this->action->execute($this->node, 101, ['user_id' => $this->owner->id]);

    expect($server->addresses()->count())->toBe(0)
        ->and($server->flagged_at)->not->toBeNull()
        ->and($server->flag_reason)->toContain('1.1.1.2')
        // The panel does not own an address it could not claim, so it must not
        // rewrite ipconfig0 out from under the running guest.
        ->and($server->ipconfig_managed)->toBeFalse();

    // And the other server still has it.
    expect(Address::query()->where('ip', '1.1.1.2')->sole()->server_id)->toBe($other->id);
});

it('leaves a dhcp guest with ipconfig unmanaged so nothing overwrites its lease', function () {
    fakeAdoptionReads([clusterGuest(101, 'pve1')], ['ipconfig0' => 'ip=dhcp']);

    $server = $this->action->execute($this->node, 101, ['user_id' => $this->owner->id]);

    expect($server->ipconfig_managed)->toBeFalse()
        ->and($server->addresses()->count())->toBe(0);
});

it('refuses a vmid the cluster already holds', function () {
    Server::factory()->create([
        'user_id' => $this->owner->id,
        'node_id' => $this->node->id,
        'storage_id' => $this->storage->id,
        'vmid' => 101,
    ]);
    fakeAdoptionReads([clusterGuest(101, 'pve1')]);

    expect(fn () => $this->action->execute($this->node, 101, ['user_id' => $this->owner->id]))
        ->toThrow(AdoptionRefusedException::class);
});

it('serves the list and adopts over the admin API', function () {
    fakeAdoptionReads([clusterGuest(101, 'pve1')]);
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->getJson('/api/admin/adoptable-guests')
        ->assertOk()
        ->assertJsonPath('data.guests.data.0.vmid', 101);

    $this->actingAs($admin)
        ->getJson("/api/admin/nodes/{$this->node->id}/adoptable-guests/101")
        ->assertOk()
        ->assertJsonPath('data.addresses.data.0.ip', '1.1.1.2');

    $this->actingAs($admin)
        ->postJson("/api/admin/nodes/{$this->node->id}/adoptable-guests/101", [
            'user_id' => $this->owner->id,
            'name' => 'adopted',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'adopted');

    expect(Server::query()->where('vmid', 101)->exists())->toBeTrue();
});
