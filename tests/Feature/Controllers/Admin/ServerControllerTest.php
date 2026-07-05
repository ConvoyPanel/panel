<?php

use App\Enums\Server\State;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('lets a root admin send a power command to any server', function () {
    fakeProxmox();

    // The admin is deliberately NOT the server's owner — the admin surface is
    // scoped to root_admin, not ownership.
    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'start'])
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
        ->assertJsonPath('data.state', State::RUNNING->value);
});

it('rejects a non-admin from the admin power endpoint', function () {
    fakeProxmox();

    // Even the server's own owner must not reach the ADMIN surface; the admin
    // route group is gated by AdminAuthenticate (root_admin only).
    [$owner, $_, $_, $server] = createServerModel();

    $this->actingAs($owner)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'start'])
        ->assertForbidden();

    Http::assertNothingSent();
});

it('validates the power command', function () {
    fakeProxmox();

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'explode'])
        ->assertUnprocessable();

    Http::assertNothingSent();
});
