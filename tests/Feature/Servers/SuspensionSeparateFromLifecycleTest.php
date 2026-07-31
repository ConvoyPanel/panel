<?php

use App\Enums\Server\ServerLifecycle;
use App\Enums\Server\SuspensionAction;
use App\Models\Server;
use App\Models\User;
use App\Services\Servers\ServerSuspensionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Cache::flush());

it('suspends without disturbing the lifecycle', function () {
    fakeProxmox();
    [$_owner, $_location, $_node, $server] = createServerModel();
    $server->update(['lifecycle' => ServerLifecycle::INSTALLING]);

    app(ServerSuspensionService::class)->toggle($server);

    // The whole point of the split: the stage the server was at survives the suspension,
    // where the old single-column scheme overwrote it and unsuspended back to `ready`.
    expect($server->fresh()->lifecycle)->toBe(ServerLifecycle::INSTALLING)
        ->and($server->fresh()->isSuspended())->toBeTrue();
});

it('unsuspends without disturbing the lifecycle', function () {
    fakeProxmox();
    [$_owner, $_location, $_node, $server] = createServerModel();
    $server->update([
        'lifecycle' => ServerLifecycle::INSTALL_FAILED,
        'suspended_at' => now(),
    ]);

    app(ServerSuspensionService::class)->toggle($server, SuspensionAction::UNSUSPEND);

    expect($server->fresh()->lifecycle)->toBe(ServerLifecycle::INSTALL_FAILED)
        ->and($server->fresh()->isSuspended())->toBeFalse();
});

it('restores the original suspension time when unsuspending fails', function () {
    Http::fake(['*' => Http::response(['errors' => ['boom']], 500)]);
    [$_owner, $_location, $_node, $server] = createServerModel();
    $suspendedAt = now()->subDays(3)->startOfSecond();
    $server->update(['suspended_at' => $suspendedAt]);

    expect(fn () => app(ServerSuspensionService::class)->toggle($server, SuspensionAction::UNSUSPEND))
        ->toThrow(Exception::class);

    // Rolled back to the time it was actually suspended, not to "now".
    expect($server->fresh()->suspended_at->equalTo($suspendedAt))->toBeTrue();
});

it('blocks client API access to a suspended server that is otherwise ready', function () {
    [$owner, $_location, $_node, $server] = createServerModel();
    $server->update([
        'lifecycle' => ServerLifecycle::READY,
        'suspended_at' => now(),
    ]);

    // `isReady()` is true here. Before the split, suspension lived in the same column and
    // made it false; now the gate has to check both axes or this request sails through.
    $this->actingAs($owner)
        ->getJson("/api/client/servers/{$server->uuid}/backups")
        ->assertConflict();
});

it('blocks reinstalling a suspended server', function () {
    [$owner, $_location, $_node, $server] = createServerModel();
    $server->update([
        'lifecycle' => ServerLifecycle::READY,
        'suspended_at' => now(),
    ]);

    // Exempt from the access middleware, so its own authorize() carries the check.
    $this->actingAs($owner)
        ->postJson("/api/client/servers/{$server->uuid}/settings/reinstall", [])
        ->assertForbidden();
});

it('blocks retrying installation on a suspended server', function () {
    [$owner, $_location, $_node, $server] = createServerModel();
    $server->update([
        'lifecycle' => ServerLifecycle::INSTALL_FAILED,
        'suspended_at' => now(),
    ]);

    $this->actingAs($owner)
        ->postJson("/api/client/servers/{$server->uuid}/retry-installation")
        ->assertForbidden();
});

it('exposes lifecycle and suspension as separate fields', function () {
    [$_owner, $_location, $_node, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);
    $server->update([
        'lifecycle' => ServerLifecycle::READY,
        'suspended_at' => now(),
    ]);

    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}")
        ->assertOk()
        ->assertJsonPath('data.lifecycle', ServerLifecycle::READY->value)
        ->assertJsonPath('data.suspendedAt', fn ($value) => $value !== null);
});

it('counts suspended servers alongside their lifecycle rather than instead of it', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    [$_owner, $_location, $node, $server] = createServerModel();
    $server->update([
        'lifecycle' => ServerLifecycle::READY,
        'suspended_at' => now(),
    ]);
    Server::factory()->for($node)->for($admin)->create(['lifecycle' => ServerLifecycle::READY]);

    // Two ready servers, one of which is also suspended. The suspended count overlaps the
    // ready count instead of carving a server out of it.
    $this->actingAs($admin)
        ->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.servers.total', 2)
        ->assertJsonPath('data.servers.ready', 2)
        ->assertJsonPath('data.servers.suspended', 1);
});
