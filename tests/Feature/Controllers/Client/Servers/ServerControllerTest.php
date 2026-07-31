<?php

use App\Enums\Anchor\AnchorMode;
use App\Enums\Server\State;
use App\Models\Anchor;
use App\Models\User;
use App\Services\Api\JWTService;
use App\Services\Nodes\GuestStateCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Cache::flush());

it('only lists servers the authenticated user owns', function () {
    [$owner, $_, $_, $server] = createServerModel();

    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(0, 'items');

    $this->actingAs($owner)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.uuid', $server->uuid);
});

it('does not list other users servers for root admins', function () {
    // The client area is owner-scoped even for admins; they must not see
    // servers they do not own here (the admin area is for that).
    [$_owner, $_, $_, $_server] = createServerModel();

    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(0, 'items');
});

it('lets a server owner send a power command', function () {
    fakeProxmox();

    [$owner, $_, $_, $server] = createServerModel();

    $this->actingAs($owner)
        ->patchJson("/api/client/servers/{$server->uuid}/state", ['state' => 'shutdown'])
        ->assertNoContent();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/status/shutdown'));
});

it('writes a live state read back into the guest state cache', function () {
    // The server list reads the cache and never PVE, so without this the badge
    // there stays stale for up to a poll interval after a power action. The
    // detail page has just paid for a live read; recording it is free.
    [$owner, $_, $node, $server] = createServerModel();

    app(GuestStateCache::class)->put($node, [$server->vmid => State::STOPPED->value]);
    $this->travel(1)->seconds();

    Http::fake(['*/status/current' => Http::response(['data' => [
        'status' => State::RUNNING->value,
        'uptime' => 120,
        'cpu' => 0.1,
        'maxmem' => 1024,
        'mem' => 512,
    ]], 200)]);

    $this->actingAs($owner)
        ->getJson("/api/client/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.state', State::RUNNING->value);

    expect(app(GuestStateCache::class)->stateFor($server->fresh()))->toBe(State::RUNNING);

    $this->actingAs($owner)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonPath('items.0.powerState', State::RUNNING->value);
});

it('does not let a non-owner send a power command', function () {
    fakeProxmox();

    [$_owner, $_, $_, $server] = createServerModel();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->patchJson("/api/client/servers/{$server->uuid}/state", ['state' => 'shutdown'])
        ->assertNotFound();

    Http::assertNothingSent();
});

it('can generate noVNC authorization token', function () {
    [$user, $_, $node, $server] = createServerModel();
    $anchor = Anchor::factory()->enrolled()->create([
        'public_url' => 'https://agent.example.com/anchor',
    ]);
    $node->update(['anchor_id' => $anchor->id]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session", [
            'type' => 'novnc',
        ],
    );

    $response->assertCreated()
        ->assertJsonPath('data.url', 'wss://agent.example.com/anchor/api/v1/console')
        ->assertJsonPath('data.protocol', Anchor::PROTOCOL_VERSION)
        ->assertJsonPath('data.type', 'novnc')
        ->assertJson(fn ($json) => $json
            ->whereType('data.password', 'string')
            ->etc());

    $token = app(JWTService::class)->decode($anchor->secret, $response->json('data.token'));
    $password = $response->json('data.password');
    expect($token->isPermittedFor($anchor->uuid))->toBeTrue()
        ->and($token->isRelatedTo($user->uuid))->toBeTrue()
        ->and($password)->toHaveLength(8)
        ->and($token->claims()->get('console'))->toBe([
            'type' => 'qemu_vnc',
            'vm_id' => $server->vmid,
            'password' => $password,
        ]);

    Http::assertNothingSent();
});

it('nests the agent session inside a relay session', function () {
    [$user, $_, $node, $server] = createServerModel();
    $relay = Anchor::factory()->enrolled()->create([
        'mode' => AnchorMode::RELAY,
        'public_url' => 'https://relay.example.com',
        'capabilities' => ['console.relay'],
    ]);
    $agent = Anchor::factory()->enrolled()->create([
        'public_url' => 'https://agent.internal.example.com',
        'relay_id' => $relay->id,
    ]);
    $node->update(['anchor_id' => $agent->id]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session",
        ['type' => 'xtermjs'],
    );

    $response->assertCreated()
        ->assertJsonPath('data.url', 'wss://relay.example.com/api/v1/console')
        ->assertJsonPath('data.password', null);

    $outer = app(JWTService::class)->decode($relay->secret, $response->json('data.token'));
    $relayClaim = $outer->claims()->get('relay');
    expect($relayClaim['url'])->toBe('wss://agent.internal.example.com/api/v1/console');

    $inner = app(JWTService::class)->decode($agent->secret, $relayClaim['token']);
    expect($inner->isPermittedFor($agent->uuid))->toBeTrue()
        ->and($inner->claims()->get('console')['type'])->toBe('qemu_terminal');
});

it('fails clearly when no Anchor agent is configured', function () {
    [$user, $_, $_, $server] = createServerModel();

    $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session",
        ['type' => 'novnc'],
    )->assertConflict()
        ->assertJsonPath('message', 'This server does not have an Anchor agent configured.');
});

it('probes a stale Anchor before refusing the session', function () {
    // The Anchor is enrolled but its heartbeat has not landed in a while --
    // which can mean it cannot reach us, not that it is down. It must still be
    // reachable the other way round.
    [$user, $_, $node, $server] = createServerModel();
    $anchor = Anchor::factory()->enrolled()->create([
        'public_url' => 'https://agent.example.com',
        'last_seen_at' => now()->subHour(),
        'version' => '0.0.1-stale',
    ]);
    $node->update(['anchor_id' => $anchor->id]);

    Http::fake(['agent.example.com/api/v1/info' => Http::response([
        'version' => '0.1.0-alpha.1',
        'mode' => 'agent',
        'protocol' => ['min' => Anchor::PROTOCOL_VERSION, 'max' => Anchor::PROTOCOL_VERSION],
        'capabilities' => ['console.qemu.vnc'],
    ])]);

    $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session",
        ['type' => 'novnc'],
    )->assertCreated();

    // The probe stands in for a heartbeat, so the reported build and
    // capabilities are refreshed too.
    $anchor->refresh();
    expect($anchor->version)->toBe('0.1.0-alpha.1')
        ->and($anchor->capabilities)->toBe(['console.qemu.vnc'])
        ->and($anchor->last_seen_at->isAfter(now()->subMinute()))->toBeTrue();
});

it('still refuses the session when a stale Anchor cannot be reached either', function () {
    [$user, $_, $node, $server] = createServerModel();
    $anchor = Anchor::factory()->enrolled()->create([
        'public_url' => 'https://agent.example.com',
        'last_seen_at' => now()->subHour(),
    ]);
    $node->update(['anchor_id' => $anchor->id]);

    Http::fake(['agent.example.com/api/v1/info' => Http::response(status: 502)]);

    $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session",
        ['type' => 'novnc'],
    )->assertConflict()
        ->assertJsonPath('message', "Anchor {$anchor->name} is not online with a compatible protocol version.");
});

it('does not probe an Anchor that was never enrolled', function () {
    // An unenrolled Anchor has no shared secret we could trust, so reaching
    // something at its URL proves nothing. Only a stale heartbeat is probed.
    [$user, $_, $node, $server] = createServerModel();
    $anchor = Anchor::factory()->create(['public_url' => 'https://agent.example.com']);
    $node->update(['anchor_id' => $anchor->id]);

    Http::fake();

    $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session",
        ['type' => 'novnc'],
    )->assertConflict();

    Http::assertNothingSent();
});
