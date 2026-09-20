<?php

use App\Actions\Server\MigrateServerAction;
use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\MigrationDisposition;
use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Service\Server\MigrationRefusedException;
use App\Jobs\Server\CommitServerMigrationJob;
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
use App\Services\Servers\ServerNetworkService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->cluster = Cluster::factory()->create();
    $location = Location::factory()->create();
    $this->source = Node::factory()->for($location)->create(['name' => 'pve1', 'cluster_id' => $this->cluster->id]);
    $this->target = Node::factory()->for($location)->create(['name' => 'pve2', 'cluster_id' => $this->cluster->id]);

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

    $this->held = Address::factory()->for($this->block)->create([
        'ip' => '192.0.2.10',
        'server_id' => $this->server->id,
        'state' => AddressState::Assigned,
    ]);
    $this->server->forceFill(['primary_ipv4_address_id' => $this->held->id])->save();

    // Deliberately no Http::fake here: Http::fake() merges its stubs and the
    // first match wins, so a per-test override registered afterwards would
    // never be reached. Each test arms the preflight it needs.
    $this->action = app(MigrateServerAction::class);
});

/** A destination with a same-named bridge on the same pool: the IP follows. */
function preservingDestination(NetworkInterface|Node $target, AddressBlockGroup $pool): NetworkInterface
{
    $bridge = NetworkInterface::create(['node_id' => $target->id, 'name' => 'vmbr0']);
    $bridge->addressBlockGroups()->attach($pool->id);

    return $bridge;
}

/** A destination fronting a different network, with one free address on it. */
function reallocatingDestination(Node $target): array
{
    $pool = AddressBlockGroup::factory()->create(['name' => 'Rack B /24']);
    $block = AddressBlock::factory()->for($pool, 'addressBlockGroup')
        ->create(['base_ip' => '198.51.100.0', 'gateway' => '198.51.100.1', 'prefix_length_from' => 24, 'prefix_length_to' => 32]);
    $free = Address::factory()->for($block)->create(['ip' => '198.51.100.20', 'server_id' => null]);

    $bridge = NetworkInterface::create(['node_id' => $target->id, 'name' => 'vmbr1']);
    $bridge->addressBlockGroups()->attach($pool->id);

    return [$bridge, $free];
}

it('queues a migrate deployment and marks the server migrating', function () {
    Bus::fake();
    fakeMigrationPreflight();
    preservingDestination($this->target, $this->pool);

    $deployment = $this->action->execute($this->server, $this->target, false);

    expect($deployment->type)->toBe(DeploymentType::MIGRATE)
        ->and($this->server->fresh()->lifecycle)->toBe(ServerLifecycle::MIGRATING)
        // Nothing has moved yet, so the row still describes where the guest is.
        ->and($this->server->fresh()->node_id)->toBe($this->source->id);

    expect($deployment->steps()->pluck('name')->all())->toBe(['migrate-vm', 'rebind-network']);
});

it('refuses a reallocation that was not acknowledged', function () {
    Bus::fake();
    fakeMigrationPreflight();
    reallocatingDestination($this->target);

    expect(fn () => $this->action->execute($this->server, $this->target, false))
        ->toThrow(MigrationRefusedException::class);
});

it('reserves the destination address before the task is issued, and leaves the old one assigned', function () {
    fakeMigrationPreflight();
    // Neutron's rule: the destination binding exists and is validated before
    // the guest is committed to the move, and the source is not released until
    // the destination is confirmed.
    Bus::fake();
    [, $free] = reallocatingDestination($this->target);

    $this->action->execute($this->server, $this->target, true);

    expect($free->fresh())
        ->state->toBe(AddressState::Reserved)
        ->state_reason->toBe(AddressStateReason::Migration)
        ->server_id->toBeNull();

    expect($this->held->fresh())
        ->state->toBe(AddressState::Assigned)
        ->server_id->toBe($this->server->id);
});

it('stops and restarts a running guest whose addresses cannot follow', function () {
    Bus::fake();
    fakeMigrationPreflight(['running' => true]);
    reallocatingDestination($this->target);

    $deployment = $this->action->execute($this->server, $this->target, true);

    expect($deployment->steps()->pluck('name')->all())
        ->toBe(['stop-vm', 'migrate-vm', 'rebind-network', 'start-vm']);
});

it('commits the rebind only once the guest has landed', function () {
    fakeMigrationPreflight();

    $bridge = preservingDestination($this->target, $this->pool);
    $deployment = $this->action->execute($this->server, $this->target, false);
    $step = $deployment->steps()->where('name', 'rebind-network')->firstOrFail();

    (new CommitServerMigrationJob($step, $this->target->id, $bridge->id, MigrationDisposition::Preserve))
        ->handle(app(ServerNetworkService::class));

    expect($this->server->fresh())
        ->node_id->toBe($this->target->id)
        ->network_interface_id->toBe($bridge->id);

    // A preserving migration touches no address at all.
    expect($this->held->fresh())->server_id->toBe($this->server->id)->state->toBe(AddressState::Assigned);
});

it('swaps the addresses over on a reallocating commit', function () {
    // The commit rewrites the guest's NIC and ipconfig0 on the destination, so
    // the whole config surface has to answer, not just the migrate preflight.
    fakeProxmox([
        '*/qemu/*/migrate*' => Http::response(migratePreconditions(), 200),
        '*/firewall/ipset*' => Http::response(['data' => []], 200),
    ]);

    [$bridge, $free] = reallocatingDestination($this->target);
    $deployment = $this->action->execute($this->server, $this->target, true);
    $step = $deployment->steps()->where('name', 'rebind-network')->firstOrFail();

    (new CommitServerMigrationJob(
        $step,
        $this->target->id,
        $bridge->id,
        MigrationDisposition::Reallocate,
        [$free->id],
        [$this->held->id],
    ))->handle(app(ServerNetworkService::class));

    expect($free->fresh())
        ->server_id->toBe($this->server->id)
        ->state->toBe(AddressState::Assigned)
        ->state_reason->toBeNull();

    expect($this->held->fresh())
        ->server_id->toBeNull()
        ->state->toBe(AddressState::Available);

    // The released address was the primary; a server cannot stay pointed at an
    // address it no longer holds.
    expect($this->server->fresh()->primary_ipv4_address_id)->not->toBe($this->held->id);
});

it('frees the reservation when the rebind fails for good', function () {
    Bus::fake();
    fakeMigrationPreflight();
    [, $free] = reallocatingDestination($this->target);
    $deployment = $this->action->execute($this->server, $this->target, true);
    $step = $deployment->steps()->where('name', 'rebind-network')->firstOrFail();

    (new CommitServerMigrationJob($step, $this->target->id, null, MigrationDisposition::Reallocate, [$free->id], [$this->held->id]))
        ->failed(new RuntimeException('the node went away'));

    expect($free->fresh())
        ->state->toBe(AddressState::Available)
        ->state_reason->toBeNull()
        ->server_id->toBeNull();
});

it('refuses to stack a migration on a server that is already busy', function () {
    fakeMigrationPreflight();
    preservingDestination($this->target, $this->pool);
    $admin = User::factory()->create(['root_admin' => true]);
    $this->server->forceFill(['lifecycle' => ServerLifecycle::INSTALLING])->save();

    $this->actingAs($admin)
        ->postJson("/api/admin/servers/{$this->server->uuid}/migration", ['node_id' => $this->target->id])
        ->assertStatus(409);
});

it('serves the plan and the preview over the admin API', function () {
    fakeMigrationPreflight();
    preservingDestination($this->target, $this->pool);
    $admin = User::factory()->create(['root_admin' => true]);

    // Nested data collections are wrapped by spatie/laravel-data the same way
    // a deployment's steps are (`data.steps.data.0`); the client unwraps it.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$this->server->uuid}/migration")
        ->assertOk()
        ->assertJsonPath('data.candidates.data.0.nodeName', 'pve2')
        ->assertJsonPath('data.candidates.data.0.disposition', MigrationDisposition::Preserve->value);

    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$this->server->uuid}/migration/{$this->target->id}")
        ->assertOk()
        ->assertJsonPath('data.preserved.data.0.ip', '192.0.2.10');
});
