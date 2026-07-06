<?php

use App\Models\User;
use App\Services\Servers\Power\ServerPowerLockService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // The lock lives in the (array) cache, which persists across tests in a run;
    // start each case from a clean slate so ordering can't leak a held lock.
    Cache::flush();
});

it('rejects a second power command while one is already in flight', function () {
    fakeProxmox();

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    // First command claims the lock and reaches Proxmox.
    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'start'])
        ->assertNoContent();

    // Second, while the lock is held, is rejected with the curated 409 code and
    // never touches Proxmox.
    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'shutdown'])
        ->assertConflict()
        ->assertJsonPath('code', 'power_action_in_progress');

    // Exactly one power request went out (the first). The shutdown was blocked.
    Http::assertSentCount(1);
});

it('surfaces the pending power action on the server state', function () {
    fakeProxmox([
        '*/status/current' => Http::response(['data' => [
            'status' => 'stopped',
            'uptime' => 0,
            'cpu' => 0,
            'maxmem' => 2147483648,
            'mem' => 0,
        ]], 200),
    ]);

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    // No action in flight yet.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.pendingPowerAction', null);

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'start'])
        ->assertNoContent();

    // Now the state echoes which command is holding the lock.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.pendingPowerAction.command', 'start');
});

it('lets a different server be powered while another holds its lock', function () {
    fakeProxmox();

    [$_owner, $_, $_, $serverA] = createServerModel();
    [$_owner2, $_, $_, $serverB] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    // Lock is per-server, so B is unaffected by A's in-flight action.
    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$serverA->uuid}/state", ['state' => 'start'])
        ->assertNoContent();

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$serverB->uuid}/state", ['state' => 'start'])
        ->assertNoContent();

    Http::assertSentCount(2);

    expect(app(ServerPowerLockService::class)->pending($serverA))->not->toBeNull();
    expect(app(ServerPowerLockService::class)->pending($serverB))->not->toBeNull();
});
