<?php

use App\Models\Location;
use App\Models\Node;
use App\Models\Storage;
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
function fakeLiveStorage(string $name, int $total, int $used, int $avail): void
{
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
                'content' => 'images,rootdir',
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
