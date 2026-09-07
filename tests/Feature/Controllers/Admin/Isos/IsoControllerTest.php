<?php

use App\Models\ISO;
use App\Models\Location;
use App\Models\Node;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create(['root_admin' => true]);
    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
    $this->storage = Storage::factory()->create(['stores_iso' => true]);
    $this->node->storages()->attach($this->storage);
});

it('lists the library without naming a node', function () {
    ISO::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->getJson('/api/admin/isos')
        ->assertOk()
        ->assertJsonCount(3, 'items');
});

it('adds an ISO from a URL, without downloading anything', function () {
    // Adding is a panel-level act now: no node is chosen, nothing is
    // transferred, and no download job is queued. The first node to mount it
    // is the one that fetches it.
    $this->actingAs($this->user)
        ->postJson('/api/admin/isos', [
            'name' => 'Debian 12',
            'file_name' => 'debian-12.iso',
            'url' => 'https://example.invalid/debian-12.iso',
            'sha256' => str_repeat('a', 64),
            'hidden' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('data.isHosted', false);

    expect(ISO::where('file_name', 'debian-12.iso')->exists())->toBeTrue();
});

it('refuses an ISO with neither a URL nor an uploaded file', function () {
    $this->actingAs($this->user)
        ->postJson('/api/admin/isos', [
            'name' => 'Nowhere',
            'file_name' => 'nowhere.iso',
            'hidden' => false,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['url', 'path']);
});

it('refuses an ISO claiming both a URL and an upload', function () {
    // Both answer the same question, so accepting both would only leave a
    // question about which one a node was handed.
    $this->actingAs($this->user)
        ->postJson('/api/admin/isos', [
            'name' => 'Both',
            'file_name' => 'both.iso',
            'url' => 'https://example.invalid/both.iso',
            'path' => 'iso-'.str_repeat('b', 64).'.iso',
            'hidden' => false,
        ])
        ->assertStatus(422);
});

it("won't reuse a file name another ISO already answers to", function () {
    ISO::factory()->create(['file_name' => 'duplicate.iso']);

    $this->actingAs($this->user)
        ->postJson('/api/admin/isos', [
            'name' => 'Test ISO',
            'file_name' => 'duplicate.iso',
            'url' => 'https://example.invalid/duplicate.iso',
            'hidden' => false,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file_name');
});

it('renames an ISO', function () {
    $iso = ISO::factory()->create();

    $this->actingAs($this->user)
        ->putJson("/api/admin/isos/{$iso->uuid}", ['name' => 'Updated ISO'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated ISO');
});

it('leaves the source alone on update', function () {
    // Nodes have already fetched against the file name and hash; changing
    // either would leave copies on disk that no longer match the record.
    $iso = ISO::factory()->create(['file_name' => 'original.iso']);

    $this->actingAs($this->user)
        ->putJson("/api/admin/isos/{$iso->uuid}", [
            'name' => 'Renamed',
            'file_name' => 'rewritten.iso',
        ])
        ->assertOk();

    expect($iso->fresh()->file_name)->toBe('original.iso');
});

it('deletes an ISO without touching any node', function () {
    // Copies already on nodes are left where they are: reaching into every
    // node to reclaim cache space is a sweep that half-completes.
    Http::preventStrayRequests();

    $iso = ISO::factory()->create();

    $this->actingAs($this->user)
        ->deleteJson("/api/admin/isos/{$iso->uuid}")
        ->assertNoContent();

    expect(ISO::find($iso->id))->toBeNull();
});

it('inspects a link through whichever node can answer', function () {
    Http::fake([
        '*/query-url-metadata*' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Node/Storage/QueryIsoData.json'),
            ),
            200,
        ),
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/admin/isos/query-remote-file?link='.urlencode(
            'https://example.invalid/virtio-win.iso',
        ))
        ->assertOk();
});

it('says so when no node can inspect a link', function () {
    Storage::query()->update(['stores_iso' => false]);

    $this->actingAs($this->user)
        ->getJson('/api/admin/isos/query-remote-file?link='.urlencode(
            'https://example.invalid/virtio-win.iso',
        ))
        ->assertStatus(409);
});
