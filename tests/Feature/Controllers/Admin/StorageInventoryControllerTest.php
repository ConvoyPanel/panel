<?php

use App\Models\Location;
use App\Models\Node;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create(['root_admin' => true]);
    $this->location = Location::factory()->create();
});

it('lists every storage across every node', function () {
    $one = Node::factory()->for($this->location)->create(['display_name' => 'pve-1']);
    $two = Node::factory()->for($this->location)->create(['display_name' => 'pve-2']);

    $shared = Storage::factory()->create(['name' => 'ceph-vm']);
    $local = Storage::factory()->create(['name' => 'local-lvm']);
    $one->storages()->attach($shared);
    $two->storages()->attach($shared);
    $one->storages()->attach($local);

    $rows = $this->actingAs($this->user)
        ->getJson('/api/admin/storages')
        ->assertOk()
        ->json('data');

    expect($rows)->toHaveCount(2);

    // With no node in scope, every node the storage reaches is named -- that is
    // the column the whole page exists for.
    $byName = collect($rows)->keyBy('name');
    expect($byName['ceph-vm']['sharedWith'])->toBe(['pve-1', 'pve-2'])
        ->and($byName['local-lvm']['sharedWith'])->toBe(['pve-1']);
});

it('reads recorded capacity without calling Proxmox', function () {
    // A fleet page cannot afford one live call per node: an unreachable node
    // among them would stall the whole list for a full connect timeout.
    Http::preventStrayRequests();

    $node = Node::factory()->for($this->location)->create();
    $storage = Storage::factory()->create(['name' => 'local']);
    $node->storages()->attach($storage);
    $storage->forceFill([
        'pve_type' => 'dir',
        'discovered_total' => 1_000,
        'discovered_used' => 400,
        'discovered_at' => now(),
    ])->save();

    $row = $this->actingAs($this->user)
        ->getJson('/api/admin/storages')
        ->assertOk()
        ->json('data.0');

    expect($row['capacitySource'])->toBe('recorded')
        ->and($row['physicalTotal'])->toBe(1_000)
        ->and($row['physicalUsed'])->toBe(400)
        ->and($row['online'])->toBeFalse();
});

it('omits a storage no node reaches', function () {
    $node = Node::factory()->for($this->location)->create();
    $attached = Storage::factory()->create(['name' => 'local']);
    Storage::factory()->create(['name' => 'orphan']);
    $node->storages()->attach($attached);

    // Same rule the admin overview uses for fleet capacity: unreachable by every
    // node means undeployable, so it is not inventory.
    $names = $this->actingAs($this->user)
        ->getJson('/api/admin/storages')
        ->assertOk()
        ->json('data.*.name');

    expect($names)->toBe(['local']);
});

it('requires an admin user', function () {
    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->getJson('/api/admin/storages')
        ->assertForbidden();
});
