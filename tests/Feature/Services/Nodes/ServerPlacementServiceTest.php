<?php

use App\Data\Cluster\ServerResourceData;
use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\Cluster;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Services\Nodes\ServerPlacementService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** One `type=qemu` row as `/cluster/resources` reports it, placement fields only. */
function placementGuest(int $vmid, string $nodeName, array $extra = []): ServerResourceData
{
    return ServerResourceData::fromRaw(array_merge([
        'id' => "qemu/{$vmid}",
        'name' => "vm-{$vmid}",
        'status' => 'running',
        'vmid' => $vmid,
        'node' => $nodeName,
    ], $extra));
}

beforeEach(function () {
    // Same trap as NodeStatusPollServiceTest: the array cache outlives the
    // database between tests, so a previous test's per-cluster placement lock
    // would make this test's reconcile a silent no-op.
    Cache::flush();

    $this->cluster = Cluster::factory()->create();
    $this->nodeA = Node::factory()->for(Location::factory())
        ->create(['name' => 'pve1', 'cluster_id' => $this->cluster->id]);
    $this->nodeB = Node::factory()->for(Location::factory())
        ->create(['name' => 'pve2', 'cluster_id' => $this->cluster->id]);

    $this->service = app(ServerPlacementService::class);
});

it('re-homes a server whose guest is reported on another registered member', function () {
    $server = Server::factory()->create(['node_id' => $this->nodeA->id, 'vmid' => 150]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve2')]));

    expect($server->fresh())
        ->node_id->toBe($this->nodeB->id)
        ->flagged_at->toBeNull();

    expect(AuditLog::query()->where('event', AuditEvent::ADMIN_SERVER_REHOMED)->exists())->toBeTrue();
});

it('leaves a server alone when the guest is on the recorded node or absent', function () {
    $inPlace = Server::factory()->create(['node_id' => $this->nodeA->id, 'vmid' => 150]);
    $absent = Server::factory()->create(['node_id' => $this->nodeA->id, 'vmid' => 151]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve1')]));

    expect($inPlace->fresh())->node_id->toBe($this->nodeA->id)->flagged_at->toBeNull();
    expect($absent->fresh())->node_id->toBe($this->nodeA->id)->flagged_at->toBeNull();
});

it('moves the interface link to the same-named bridge on the new node', function () {
    $bridgeA = NetworkInterface::factory()->create(['node_id' => $this->nodeA->id, 'name' => 'vmbr0']);
    $bridgeB = NetworkInterface::factory()->create(['node_id' => $this->nodeB->id, 'name' => 'vmbr0']);
    $server = Server::factory()->create([
        'node_id' => $this->nodeA->id,
        'network_interface_id' => $bridgeA->id,
        'vmid' => 150,
    ]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve2')]));

    expect($server->fresh())
        ->node_id->toBe($this->nodeB->id)
        ->network_interface_id->toBe($bridgeB->id)
        ->flagged_at->toBeNull();
});

it('clears the interface link and flags when the new node has no such bridge', function () {
    $bridgeA = NetworkInterface::factory()->create(['node_id' => $this->nodeA->id, 'name' => 'vmbr0']);
    $server = Server::factory()->create([
        'node_id' => $this->nodeA->id,
        'network_interface_id' => $bridgeA->id,
        'vmid' => 150,
    ]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve2')]));

    expect($server->fresh())
        ->node_id->toBe($this->nodeB->id)
        ->network_interface_id->toBeNull()
        ->flagged_at->not->toBeNull()
        ->flag_reason->toContain('vmbr0');
});

it('flags instead of re-homing when the guest is on a member Convoy does not manage', function () {
    $server = Server::factory()->create(['node_id' => $this->nodeA->id, 'vmid' => 150]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve9')]));

    expect($server->fresh())
        ->node_id->toBe($this->nodeA->id)
        ->flagged_at->not->toBeNull()
        ->flag_reason->toContain('pve9');
});

it('waits out a guest that is mid-migration', function () {
    $server = Server::factory()->create(['node_id' => $this->nodeA->id, 'vmid' => 150]);

    $this->service->reconcile($this->cluster, collect([
        placementGuest(150, 'pve2', ['lock' => 'migrate']),
    ]));

    expect($server->fresh())->node_id->toBe($this->nodeA->id)->flagged_at->toBeNull();
});

it('flags every holder of an ambiguous vmid instead of guessing which one moved', function () {
    $one = Server::factory()->create(['node_id' => $this->nodeA->id, 'vmid' => 150]);
    $other = Server::factory()->create(['node_id' => $this->nodeB->id, 'vmid' => 150]);

    // Reported on a third member, so *both* rows mismatch and neither wins.
    $nodeC = Node::factory()->for(Location::factory())
        ->create(['name' => 'pve3', 'cluster_id' => $this->cluster->id]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve3')]));

    expect($one->fresh())->node_id->toBe($this->nodeA->id)->flagged_at->not->toBeNull();
    expect($other->fresh())->node_id->toBe($this->nodeB->id)->flagged_at->not->toBeNull();
});

it('does nothing for standalone or flagged scopes', function () {
    $standalone = Cluster::factory()->standalone()->create();
    $this->nodeA->forceFill(['cluster_id' => $standalone->id])->save();
    $server = Server::factory()->create(['node_id' => $this->nodeA->id, 'vmid' => 150]);

    $this->service->reconcile($standalone, collect([placementGuest(150, 'pve2')]));
    expect($server->fresh())->node_id->toBe($this->nodeA->id);

    $this->nodeA->forceFill(['cluster_id' => $this->cluster->id])->save();
    $this->cluster->forceFill(['flagged_at' => now(), 'flag_reason' => 'disjoint members'])->save();

    $this->service->reconcile($this->cluster->fresh(), collect([placementGuest(150, 'pve2')]));
    expect($server->fresh())->node_id->toBe($this->nodeA->id);
});

it('confirms a stamped identity against the target config before re-homing', function () {
    $uuid = 'd9c9d5f0-5c60-4a6e-9c69-000000000001';
    $server = Server::factory()->create([
        'node_id' => $this->nodeA->id,
        'vmid' => 150,
        'smbios_uuid' => $uuid,
    ]);

    Http::fake([
        '*/api2/json/nodes/pve2/qemu/150/config' => Http::response([
            'data' => ['smbios1' => "uuid={$uuid},manufacturer=QW5vbnltb3Vz"],
        ]),
    ]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve2')]));

    expect($server->fresh())->node_id->toBe($this->nodeB->id)->flagged_at->toBeNull();
});

it('flags a stamped server whose target guest carries a different identity', function () {
    $server = Server::factory()->create([
        'node_id' => $this->nodeA->id,
        'vmid' => 150,
        'smbios_uuid' => 'd9c9d5f0-5c60-4a6e-9c69-000000000001',
    ]);

    Http::fake([
        '*/api2/json/nodes/pve2/qemu/150/config' => Http::response([
            'data' => ['smbios1' => 'uuid=ffffffff-ffff-ffff-ffff-ffffffffffff'],
        ]),
    ]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve2')]));

    expect($server->fresh())
        ->node_id->toBe($this->nodeA->id)
        ->flagged_at->not->toBeNull()
        ->flag_reason->toContain('SMBIOS');
});

it('re-homes an unstamped server without asking the target anything', function () {
    Http::fake();

    $server = Server::factory()->create(['node_id' => $this->nodeA->id, 'vmid' => 150]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve2')]));

    expect($server->fresh())->node_id->toBe($this->nodeB->id);
    Http::assertNothingSent();
});

it('clears a standing flag when a clean re-home resolves it', function () {
    $server = Server::factory()->create([
        'node_id' => $this->nodeA->id,
        'vmid' => 150,
        'flagged_at' => now(),
        'flag_reason' => 'Guest 150 moved to cluster member "pve2", which is not registered in Convoy.',
    ]);

    $this->service->reconcile($this->cluster, collect([placementGuest(150, 'pve2')]));

    expect($server->fresh())
        ->node_id->toBe($this->nodeB->id)
        ->flagged_at->toBeNull()
        ->flag_reason->toBeNull();
});

it('scopes vmid uniqueness to the cluster for clustered nodes', function () {
    Server::factory()->create(['node_id' => $this->nodeA->id, 'vmid' => 150]);

    // Same cluster, different node: taken.
    expect(Server::isUniqueVmId($this->nodeB, 150))->toBeFalse();

    // A standalone node elsewhere only answers for itself.
    $standalone = Cluster::factory()->standalone()->create();
    $lone = Node::factory()->for(Location::factory())
        ->create(['name' => 'solo', 'cluster_id' => $standalone->id]);

    expect(Server::isUniqueVmId($lone, 150))->toBeTrue();
});
