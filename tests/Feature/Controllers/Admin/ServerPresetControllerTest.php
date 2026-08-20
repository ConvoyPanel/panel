<?php

use App\Models\NetworkInterface;
use App\Models\Node;
// Storage is attached to nodes through a pivot, so a storage needs no node to
// exist — which is exactly the case this file's node-scoping test covers.
use App\Models\ServerPreset;
use App\Models\Storage;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
});

it('can fetch presets', function () {
    ServerPreset::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)->getJson('/api/admin/server-presets');

    $response->assertOk()->assertJsonCount(2, 'data');
});

it('can create a preset', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/admin/server-presets', [
        'name' => 'Small NVMe',
        'description' => 'The usual small build.',
        'settings' => [
            'cpu' => 2,
            'memory' => 2048,
            'disk' => 20480,
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Small NVMe')
        ->assertJsonPath('data.settings.cpu', 2);

    expect(ServerPreset::query()->where('name', 'Small NVMe')->exists())->toBeTrue();
});

it('drops settings left blank rather than storing them as null', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/server-presets', [
        'name' => 'Just the CPU',
        'settings' => [
            'cpu' => 4,
            'memory' => null,
            'disks' => [],
        ],
    ])->assertCreated();

    expect(ServerPreset::query()->where('name', 'Just the CPU')->sole()->settings)
        ->toBe(['cpu' => 4]);
});

it('saves an unmetered, uncapped preset with the -1 sentinel', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/server-presets', [
        'name' => 'No ceilings',
        'settings' => [
            'bandwidth' => -1,
            'speed_limit' => -1,
        ],
    ])->assertCreated();

    expect(ServerPreset::query()->where('name', 'No ceilings')->sole()->settings)
        ->toBe(['bandwidth' => -1, 'speed_limit' => -1]);
});

it('refuses a speed cap between the uncapped sentinel and 1 MB/s', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/server-presets', [
        'name' => 'A stopped NIC',
        'settings' => ['speed_limit' => 0],
    ])->assertStatus(422)->assertJsonValidationErrors('settings.speed_limit');
});

it('refuses a node-scoped setting saved without its node', function () {
    $storage = Storage::factory()->create();

    $response = $this->actingAs($this->admin)->postJson('/api/admin/server-presets', [
        'name' => 'Storage without a node',
        'settings' => [
            'storage_id' => $storage->id,
        ],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('settings.node_id');
});

it('refuses an interface that belongs to another node', function () {
    $node = Node::factory()->create();
    $otherNode = Node::factory()->create();
    $interface = NetworkInterface::factory()->create(['node_id' => $otherNode->id]);

    $response = $this->actingAs($this->admin)->postJson('/api/admin/server-presets', [
        'name' => 'Wrong bridge',
        'settings' => [
            'node_id' => $node->id,
            'network_interface_id' => $interface->id,
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('settings.network_interface_id');
});

it('can update a preset', function () {
    $preset = ServerPreset::factory()->create();

    $response = $this->actingAs($this->admin)->putJson("/api/admin/server-presets/{$preset->uuid}", [
        'name' => 'Renamed',
        'description' => null,
        'settings' => ['cpu' => 8],
    ]);

    $response->assertOk()->assertJsonPath('data.name', 'Renamed');

    expect($preset->refresh()->settings)->toBe(['cpu' => 8]);
});

it('lets a preset keep its own name on update', function () {
    $preset = ServerPreset::factory()->create(['name' => 'Unchanged']);

    $this->actingAs($this->admin)->putJson("/api/admin/server-presets/{$preset->uuid}", [
        'name' => 'Unchanged',
        'settings' => ['cpu' => 1],
    ])->assertOk();
});

it('refuses a duplicate name', function () {
    ServerPreset::factory()->create(['name' => 'Taken']);

    $this->actingAs($this->admin)->postJson('/api/admin/server-presets', [
        'name' => 'Taken',
        'settings' => ['cpu' => 1],
    ])->assertStatus(422)->assertJsonValidationErrors('name');
});

it('can delete a preset', function () {
    $preset = ServerPreset::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/server-presets/{$preset->uuid}")
        ->assertNoContent();

    expect(ServerPreset::query()->count())->toBe(0);
});

it('denies a non-admin', function () {
    $user = User::factory()->create(['root_admin' => false]);

    $this->actingAs($user)->getJson('/api/admin/server-presets')->assertForbidden();
});

it('round-trips a preset that carries extra disks', function () {
    $node = Node::factory()->create();
    $storage = Storage::factory()->create();

    $this->actingAs($this->admin)->postJson('/api/admin/server-presets', [
        'name' => 'Two volumes',
        'settings' => [
            'node_id' => $node->id,
            'disks' => [
                ['storage_id' => $storage->id, 'size' => 25],
                ['storage_id' => $storage->id, 'size' => 50],
            ],
        ],
    ])->assertCreated();

    // A nested data collection is wrapped in its own `data` key, the same way a
    // server's node is. The frontend transformer has to unwrap it, so pin the
    // shape here rather than only the values.
    $this->actingAs($this->admin)->getJson('/api/admin/server-presets')
        ->assertOk()
        ->assertJsonPath('data.0.settings.disks.data.0.size', 25)
        ->assertJsonPath('data.0.settings.disks.data.1.storageId', $storage->id);
});
