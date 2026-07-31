<?php

use App\Enums\Server\PowerState;
use App\Models\User;
use App\Services\Nodes\GuestStateCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Cache::flush());

it('returns cached guest power state in the admin server list', function () {
    [$_owner, $_location, $node, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    app(GuestStateCache::class)->put($node, [$server->vmid => PowerState::RUNNING->value]);

    $this->actingAs($admin)
        ->getJson('/api/admin/servers')
        ->assertOk()
        ->assertJsonPath('items.0.id', $server->id)
        ->assertJsonPath('items.0.powerState', PowerState::RUNNING->value);
});

it('lets a root admin send a power command to any server', function () {
    fakeProxmox();

    // The admin is deliberately NOT the server's owner — the admin surface is
    // scoped to root_admin, not ownership.
    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->postJson("/api/admin/servers/{$server->uuid}/power", ['command' => 'start'])
        ->assertNoContent();

    Http::assertSent(
        fn ($request) => str_contains($request->url(), '/status/start')
            && $request->method() === 'POST',
    );
});

it('lets a root admin read any server state', function () {
    fakeProxmox([
        '*/status/current' => Http::response(['data' => [
            'status' => 'running',
            'uptime' => 3600,
            'cpu' => 0.25,
            'maxmem' => 2147483648,
            'mem' => 1073741824,
        ]], 200),
    ]);

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.powerState', PowerState::RUNNING->value);
});

it('rejects a non-admin from the admin power endpoint', function () {
    fakeProxmox();

    // Even the server's own owner must not reach the ADMIN surface; the admin
    // route group is gated by AdminAuthenticate (root_admin only).
    [$owner, $_, $_, $server] = createServerModel();

    $this->actingAs($owner)
        ->postJson("/api/admin/servers/{$server->uuid}/power", ['command' => 'start'])
        ->assertForbidden();

    Http::assertNothingSent();
});

it('validates the power command', function () {
    fakeProxmox();

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->postJson("/api/admin/servers/{$server->uuid}/power", ['command' => 'explode'])
        ->assertUnprocessable();

    Http::assertNothingSent();
});

it('updates build limits and server bandwidth overrides without touching addresses', function () {
    fakeProxmox([
        '*/firewall/ipset' => Http::response(['data' => []], 200),
    ]);

    [$_owner, $_location, $node, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $node->update([
        'overage_penalty' => ['action' => 'disconnect', 'rate' => null],
    ]);

    $response = $this->actingAs($admin)->patchJson(
        "/api/admin/servers/{$server->uuid}/settings/build",
        [
            'cpu' => $server->cpu,
            'memory' => $server->memory,
            'disk' => $server->disk,
            'backup_count_limit' => 8,
            'backup_size_limit' => 50 * 1024 * 1024,
            'bandwidth_limit' => 500 * 1024 * 1024,
            'bandwidth_usage' => 25 * 1024 * 1024,
            'speed_limit' => 100_000_000,
            'overage_penalty' => [
                'action' => 'throttle',
                'rate' => 10_000_000,
            ],
        ],
    );

    $response->assertOk()
        ->assertJsonPath('data.backupCountLimit', 8)
        ->assertJsonPath('data.speedLimit', 100_000_000)
        ->assertJsonPath('data.overagePenalty.action', 'throttle')
        ->assertJsonPath('data.overagePenalty.rate', 10_000_000)
        ->assertJsonPath('data.node.overagePenalty.action', 'disconnect');

    $server->refresh();

    expect($server->backup_count_limit)->toBe(8)
        ->and($server->backup_size_limit)->toBe(50 * 1024 * 1024)
        ->and($server->bandwidth_limit)->toBe(500 * 1024 * 1024)
        ->and($server->bandwidth_usage)->toBe(25 * 1024 * 1024)
        ->and($server->speed_limit)->toBe(100_000_000)
        ->and($server->overage_penalty?->rate)->toBe(10_000_000);
});

it('clears a server speed cap and overage override back to inherited defaults', function () {
    fakeProxmox([
        '*/firewall/ipset' => Http::response(['data' => []], 200),
    ]);

    [$_owner, $_location, $_node, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $server->update([
        'speed_limit' => 25_000_000,
        'overage_penalty' => ['action' => 'disconnect', 'rate' => null],
    ]);

    $this->actingAs($admin)->patchJson(
        "/api/admin/servers/{$server->uuid}/settings/build",
        [
            'cpu' => $server->cpu,
            'memory' => $server->memory,
            'disk' => $server->disk,
            'backup_count_limit' => $server->backup_count_limit,
            'backup_size_limit' => $server->backup_size_limit,
            'bandwidth_limit' => $server->bandwidth_limit,
            'bandwidth_usage' => 0,
            'speed_limit' => null,
            'overage_penalty' => null,
        ],
    )->assertOk()
        ->assertJsonPath('data.speedLimit', null)
        ->assertJsonPath('data.overagePenalty', null);

    expect($server->refresh()->speed_limit)->toBeNull()
        ->and($server->overage_penalty)->toBeNull();
});
