<?php

use App\Models\Location;
use App\Models\Node;
use App\Models\Storage;
use App\Models\StorageToNode;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // The live-storage lookup is cached per node; keep tests independent since
    // RefreshDatabase resets sequences and node ids can repeat across tests.
    Cache::flush();

    $this->user = User::factory()->create(['root_admin' => true]);
    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();

    // 100 MiB reserve buffer (passed in bytes; StorageSizeCast stores MiB).
    $this->storage = Storage::factory()->create([
        'reserved_bytes' => 100 * 1048576,
    ]);
    $this->node->storages()->attach($this->storage);
});

/** Fake the PVE per-node storage listing endpoint with one live entry. */
function fakeLiveStorage(
    string $name,
    int $total,
    int $used,
    int $avail,
    string $content = 'images,rootdir',
): void {
    Http::fake([
        '*/storage' => Http::response([
            'data' => [[
                'storage' => $name,
                'total' => $total,
                'used' => $used,
                'avail' => $avail,
                'enabled' => 1,
                'active' => 1,
                'shared' => 0,
                'content' => $content,
            ]],
        ], 200),
    ]);
}

it('merges live Proxmox figures and derives untracked + free-for-convoy', function () {
    fakeLiveStorage($this->storage->name, total: 1_000_000_000, used: 600_000_000, avail: 400_000_000);

    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/storages",
    );

    $response->assertOk();
    $row = $response->json('data.0');

    expect($row['online'])->toBeTrue();
    expect($row['physicalTotal'])->toBe(1_000_000_000);
    expect($row['physicalUsed'])->toBe(600_000_000);
    expect($row['physicalFree'])->toBe(400_000_000);
    // No servers/backups/isos attached ⇒ Convoy committed nothing.
    expect($row['committedByConvoy'])->toBe(0);
    // Untracked = physicalUsed − committed = the base-system slice, made explicit.
    expect($row['untracked'])->toBe(600_000_000);
    // Free for Convoy = physicalFree − reserve (100 MiB).
    expect($row['freeForConvoy'])->toBe(400_000_000 - 100 * 1048576);
    expect($row['reservedBytes'])->toBe(100 * 1048576);
});

it('falls back gracefully when the node is offline', function () {
    // Connection failure on the live lookup must not fail the whole list.
    Http::fake(fn () => throw new ConnectionException('node down'));

    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/storages",
    );

    $response->assertOk();
    $row = $response->json('data.0');

    expect($row['online'])->toBeFalse();
    // Never discovered either, so there is genuinely nothing to show.
    expect($row['capacitySource'])->toBe('unknown');
    expect($row['physicalTotal'])->toBeNull();
    expect($row['physicalUsed'])->toBeNull();
    expect($row['physicalFree'])->toBeNull();
    expect($row['untracked'])->toBeNull();
    expect($row['freeForConvoy'])->toBeNull();
    // The Convoy-side record still renders.
    expect($row['name'])->toBe($this->storage->name);
    expect($row['reservedBytes'])->toBe(100 * 1048576);
});

it('shows the last recorded capacity when the node is unreachable', function () {
    // The poll wrote these; the page used to go blank the moment live failed,
    // even though the figures were sitting right here.
    $this->storage->forceFill([
        'pve_type' => 'dir',
        'discovered_total' => 1_000_000_000,
        'discovered_used' => 600_000_000,
        'discovered_at' => now()->subMinutes(3),
    ])->save();

    Http::fake(fn () => throw new ConnectionException('node down'));

    $row = $this->actingAs($this->user)
        ->getJson("/api/admin/nodes/{$this->node->id}/storages")
        ->assertOk()
        ->json('data.0');

    expect($row['online'])->toBeFalse()
        ->and($row['capacitySource'])->toBe('recorded')
        ->and($row['physicalTotal'])->toBe(1_000_000_000)
        ->and($row['physicalUsed'])->toBe(600_000_000)
        ->and($row['physicalFree'])->toBe(400_000_000)
        ->and($row['untracked'])->toBe(600_000_000)
        ->and($row['freeForConvoy'])->toBe(400_000_000 - 100 * 1048576)
        ->and($row['observedAt'])->not->toBeNull();
});

it('prefers a live figure over the recorded one', function () {
    $this->storage->forceFill([
        'discovered_total' => 1,
        'discovered_used' => 1,
        'discovered_at' => now()->subDay(),
    ])->save();

    fakeLiveStorage($this->storage->name, total: 1_000_000_000, used: 600_000_000, avail: 400_000_000);

    $row = $this->actingAs($this->user)
        ->getJson("/api/admin/nodes/{$this->node->id}/storages")
        ->assertOk()
        ->json('data.0');

    expect($row['capacitySource'])->toBe('live')
        ->and($row['physicalTotal'])->toBe(1_000_000_000);
});

it('does not report untracked space on a thin backend', function () {
    // On lvmthin a 1 TiB disk that has written 40 GiB costs 40 GiB, so the
    // ledger legitimately exceeds physical bytes. Clamping the subtraction at
    // zero would present "nothing unaccounted for" as a finding rather than an
    // artefact of arithmetic that does not apply here.
    $this->storage->forceFill(['pve_type' => 'lvmthin'])->save();

    fakeLiveStorage($this->storage->name, total: 1_000_000_000, used: 600_000_000, avail: 400_000_000);

    $row = $this->actingAs($this->user)
        ->getJson("/api/admin/nodes/{$this->node->id}/storages")
        ->assertOk()
        ->json('data.0');

    expect($row['isThin'])->toBeTrue()
        ->and($row['untracked'])->toBeNull()
        // Everything else still reports -- only the invalid subtraction is withheld.
        ->and($row['physicalUsed'])->toBe(600_000_000)
        ->and($row['freeForConvoy'])->toBe(400_000_000 - 100 * 1048576);
});

it('treats a Proxmox Backup Server datastore as thin', function () {
    // Dedup, not thin provisioning -- but the same consequence for the maths,
    // and no PBS-specific handling anywhere to arrange it.
    $this->storage->forceFill(['pve_type' => 'pbs'])->save();

    fakeLiveStorage($this->storage->name, total: 1_000_000_000, used: 600_000_000, avail: 400_000_000);

    $row = $this->actingAs($this->user)
        ->getJson("/api/admin/nodes/{$this->node->id}/storages")
        ->assertOk()
        ->json('data.0');

    expect($row['isThin'])->toBeTrue()
        ->and($row['untracked'])->toBeNull();
});

it('still reports untracked space on a thick backend', function () {
    $this->storage->forceFill(['pve_type' => 'dir'])->save();

    fakeLiveStorage($this->storage->name, total: 1_000_000_000, used: 600_000_000, avail: 400_000_000);

    $row = $this->actingAs($this->user)
        ->getJson("/api/admin/nodes/{$this->node->id}/storages")
        ->assertOk()
        ->json('data.0');

    expect($row['isThin'])->toBeFalse()
        ->and($row['untracked'])->toBe(600_000_000);
});

it('deletes a storage and its node pivot', function () {
    $this->actingAs($this->user)
        ->deleteJson("/api/admin/nodes/{$this->node->id}/storages/{$this->storage->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('storages', [
        'id' => $this->storage->id,
    ]);
    $this->assertDatabaseMissing('storage_to_node', [
        'node_id' => $this->node->id,
        'storage_id' => $this->storage->id,
    ]);
});

it('does not delete a storage through an unrelated node', function () {
    $otherNode = Node::factory()->for($this->location)->create();

    $this->actingAs($this->user)
        ->deleteJson("/api/admin/nodes/{$otherNode->id}/storages/{$this->storage->id}")
        ->assertNotFound();

    $this->assertDatabaseHas('storages', [
        'id' => $this->storage->id,
    ]);
    $this->assertDatabaseHas('storage_to_node', [
        'node_id' => $this->node->id,
        'storage_id' => $this->storage->id,
    ]);
});

it('detaches a shared storage instead of deleting it off every node', function () {
    $other = Node::factory()->for($this->location)->create();
    $other->storages()->attach($this->storage);

    $this->actingAs($this->user)
        ->deleteJson("/api/admin/nodes/{$this->node->id}/storages/{$this->storage->id}")
        ->assertNoContent();

    // Still registered, just not here. Removing it from one node's list is not a
    // statement about the pool itself.
    expect(Storage::find($this->storage->id))->not->toBeNull()
        ->and($this->node->storages()->count())->toBe(0)
        ->and($other->storages()->count())->toBe(1);
});

it('deletes a storage outright when only one node reaches it', function () {
    $this->actingAs($this->user)
        ->deleteJson("/api/admin/nodes/{$this->node->id}/storages/{$this->storage->id}")
        ->assertNoContent();

    expect(Storage::find($this->storage->id))->toBeNull();
});

it('orders backups per node rather than across all of them', function () {
    // The response re-reads live capacity; the node being offline is irrelevant
    // to ordering and keeps this test about the pivot writes.
    Http::fake(fn () => throw new ConnectionException('node down'));

    // One pool reachable from two nodes: a drag on one must not reorder the other.
    $other = Node::factory()->for($this->location)->create();
    $second = Storage::factory()->create(['stores_backups' => true]);
    $this->storage->update(['stores_backups' => true]);

    $this->node->storages()->attach($second);
    $other->storages()->attach($this->storage);
    $other->storages()->attach($second);

    StorageToNode::query()->where('node_id', $other->id)
        ->update(['backup_order' => 99]);

    $this->actingAs($this->user)
        ->putJson("/api/admin/nodes/{$this->node->id}/storages/backup-order", [
            'ids' => [$second->id, $this->storage->id],
        ])
        ->assertOk();

    $onThisNode = StorageToNode::query()->where('node_id', $this->node->id)->pluck('backup_order', 'storage_id');
    expect((int) $onThisNode[$second->id])->toBe(1)
        ->and((int) $onThisNode[$this->storage->id])->toBe(2);

    // The other node's preferences are untouched.
    expect(
        StorageToNode::query()->where('node_id', $other->id)->pluck('backup_order')->unique()->all()
    )->toBe([99]);
});

it('names the other nodes a shared storage reaches', function () {
    $peer = Node::factory()->for($this->location)->create(['display_name' => 'pve-2']);
    $peer->storages()->attach($this->storage);
    fakeLiveStorage($this->storage->name, total: 100, used: 20, avail: 80);

    $row = $this->actingAs($this->user)
        ->getJson("/api/admin/nodes/{$this->node->id}/storages")
        ->assertOk()
        ->json('data.0');

    // The node being viewed is excluded -- it is not "shared with" itself.
    expect($row['sharedWith'])->toBe([['id' => $peer->id, 'name' => 'pve-2']]);
});

it('reports no other nodes for a storage only this one reaches', function () {
    fakeLiveStorage($this->storage->name, total: 100, used: 20, avail: 80);

    $row = $this->actingAs($this->user)
        ->getJson("/api/admin/nodes/{$this->node->id}/storages")
        ->assertOk()
        ->json('data.0');

    expect($row['sharedWith'])->toBe([]);
});

it('creates a storage without a display name', function () {
    // `display_name` used to be required when the operator ticked "shareable".
    // That flag is gone -- whether a storage is shared is Proxmox's answer, and
    // it is not known at registration because nothing has polled yet.
    fakeLiveStorage('new-pool', total: 100, used: 20, avail: 80);

    $this->actingAs($this->user)
        ->postJson("/api/admin/nodes/{$this->node->id}/storages", [
            'name' => 'new-pool',
            'size' => 100 * 1048576,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'new-pool')
        ->assertJsonPath('data.displayName', null);

    expect($this->node->storages()->where('name', 'new-pool')->exists())->toBeTrue();
});

it('still bounds the display name it is given', function () {
    fakeLiveStorage('new-pool', total: 100, used: 20, avail: 80);

    $this->actingAs($this->user)
        ->postJson("/api/admin/nodes/{$this->node->id}/storages", [
            'display_name' => str_repeat('a', 41),
            'name' => 'new-pool',
            'size' => 100 * 1048576,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('display_name');
});

it('takes the content types from Proxmox, not from the request', function () {
    // The request used to carry six booleans. Nothing stops a caller sending
    // them still, and the one thing that must not happen is Convoy believing a
    // storage holds backups when PVE will refuse the write.
    fakeLiveStorage('new-pool', total: 100, used: 20, avail: 80, content: 'images,vztmpl');

    $this->actingAs($this->user)
        ->postJson("/api/admin/nodes/{$this->node->id}/storages", [
            'name' => 'new-pool',
            'size' => 100 * 1048576,
            'stores_backups' => true,
            'stores_kvm' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('data.storesKvm', true)
        ->assertJsonPath('data.storesLxcTemplates', true)
        ->assertJsonPath('data.storesBackups', false);

    expect(Storage::query()->where('name', 'new-pool')->sole())
        ->stores_kvm->toBeTrue()
        ->stores_lxc_templates->toBeTrue()
        ->stores_backups->toBeFalse();
});

it('will not let an update rewrite the content types', function () {
    fakeLiveStorage($this->storage->name, total: 100, used: 20, avail: 80);
    $this->storage->update(['stores_kvm' => true, 'stores_backups' => false]);

    $this->actingAs($this->user)
        ->putJson("/api/admin/nodes/{$this->node->id}/storages/{$this->storage->id}", [
            'name' => $this->storage->name,
            'size' => 100 * 1048576,
            'description' => 'still just a disk',
            'stores_backups' => true,
            'stores_kvm' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.description', 'still just a disk');

    expect($this->storage->refresh())
        ->stores_kvm->toBeTrue()
        ->stores_backups->toBeFalse();
});
