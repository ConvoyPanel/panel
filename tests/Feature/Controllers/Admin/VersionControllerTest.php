<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config()->set('app.version', '4.6.1');
});

$release = fn () => Http::fake(['api.github.com/*' => Http::response([
    'tag_name' => 'v4.7.0',
    'html_url' => 'https://github.com/ConvoyPanel/panel/releases/tag/v4.7.0',
    'published_at' => '2026-08-01T12:00:00Z',
])]);

it('serves the last completed check without touching GitHub', function () use ($release) {
    $admin = User::factory()->create(['root_admin' => true]);

    $release();
    $this->artisan('updates:check')->assertSuccessful();
    Http::fake();

    $this->actingAs($admin)->getJson('/api/admin/version')
        ->assertOk()
        ->assertJsonPath('data.currentVersion', '4.6.1')
        ->assertJsonPath('data.latestVersion', '4.7.0')
        ->assertJsonPath('data.status', 'update_available')
        ->assertJsonPath('data.updateAvailable', true)
        ->assertJsonPath('data.repository', 'ConvoyPanel/panel');

    // The read path must never block on a third party being reachable.
    Http::assertNothingSent();
});

it('reports an unknown status rather than failing before the first check', function () {
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)->getJson('/api/admin/version')
        ->assertOk()
        ->assertJsonPath('data.status', 'unknown')
        ->assertJsonPath('data.latestVersion', null);
});

it('checks on demand', function () use ($release) {
    $admin = User::factory()->create(['root_admin' => true]);
    $release();

    // 201 rather than 200 because that is what every POST returning a Data
    // object answers with in this app, not because anything was created.
    $this->actingAs($admin)->postJson('/api/admin/version/check')
        ->assertCreated()
        ->assertJsonPath('data.latestVersion', '4.7.0');
});

it('surfaces a failed on-demand check as unavailable', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    Http::fake(['api.github.com/*' => Http::response(status: 403)]);

    $this->actingAs($admin)->postJson('/api/admin/version/check')
        ->assertStatus(503);
});

it('is not reachable by a non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/admin/version')
        ->assertForbidden();
});
