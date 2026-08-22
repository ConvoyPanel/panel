<?php

use App\Enums\Server\PowerState;
use App\Models\Node;
use App\Models\Relay;
use App\Models\User;
use App\Services\Api\JWTService;
use App\Services\Nodes\GuestStateCache;
use App\Support\Anchor\AnchorProtocol;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        ->postJson("/api/client/servers/{$server->uuid}/power", ['command' => 'shutdown'])
        ->assertNoContent();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/status/shutdown'));
});

it('writes a live state read back into the guest state cache', function () {
    // The server list reads the cache and never PVE, so without this the badge
    // there stays stale for up to a poll interval after a power action. The
    // detail page has just paid for a live read; recording it is free.
    [$owner, $_, $node, $server] = createServerModel();

    app(GuestStateCache::class)->put($node, [$server->vmid => PowerState::STOPPED->value]);
    $this->travel(1)->seconds();

    Http::fake(['*/status/current' => Http::response(['data' => [
        'status' => PowerState::RUNNING->value,
        'uptime' => 120,
        'cpu' => 0.1,
        'maxmem' => 1024,
        'mem' => 512,
    ]], 200)]);

    $this->actingAs($owner)
        ->getJson("/api/client/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.powerState', PowerState::RUNNING->value);

    expect(app(GuestStateCache::class)->stateFor($server->fresh()))->toBe(PowerState::RUNNING);

    $this->actingAs($owner)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonPath('items.0.powerState', PowerState::RUNNING->value);
});

it('does not let a non-owner send a power command', function () {
    fakeProxmox();

    [$_owner, $_, $_, $server] = createServerModel();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->postJson("/api/client/servers/{$server->uuid}/power", ['command' => 'shutdown'])
        ->assertNotFound();

    Http::assertNothingSent();
});

it('can generate noVNC authorization token', function () {
    [$user, $_, $node, $server] = createServerModel();
    $node->update([
        ...Node::factory()->withAgent()->raw(),
        'agent_public_url' => 'https://agent.example.com/anchor',
    ]);
    $node->refresh();

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session", [
            'type' => 'novnc',
        ],
    );

    $response->assertCreated()
        ->assertJsonPath('data.url', 'wss://agent.example.com/anchor/api/v1/console')
        ->assertJsonPath('data.protocol', AnchorProtocol::VERSION)
        ->assertJsonPath('data.type', 'novnc')
        ->assertJson(fn ($json) => $json
            ->whereType('data.password', 'string')
            ->etc());

    $token = app(JWTService::class)->decode($node->agent_secret, $response->json('data.token'));
    $password = $response->json('data.password');
    expect($token->isPermittedFor($node->agent_uuid))->toBeTrue()
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
    $relay = Relay::factory()->enrolled()->create([
        'public_url' => 'https://relay.example.com',
    ]);
    $node->update([
        ...Node::factory()->withAgent()->raw(),
        'agent_public_url' => 'https://agent.internal.example.com',
        'relay_id' => $relay->id,
    ]);
    $node->refresh();

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

    $inner = app(JWTService::class)->decode($node->agent_secret, $relayClaim['token']);
    expect($inner->isPermittedFor($node->agent_uuid))->toBeTrue()
        ->and($inner->claims()->get('console')['type'])->toBe('qemu_terminal');
});

it('fails clearly when the node has no Anchor agent installed', function () {
    // The v4 shape: a node that works for everything except the console.
    [$user, $_, $_, $server] = createServerModel();

    $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session",
        ['type' => 'novnc'],
    )->assertConflict()
        ->assertJsonPath('message', "This server's node does not have an Anchor agent installed.");
});

it('probes a stale Anchor before refusing the session', function () {
    // The Anchor is enrolled but its heartbeat has not landed in a while --
    // which can mean it cannot reach us, not that it is down. It must still be
    // reachable the other way round.
    [$user, $_, $node, $server] = createServerModel();
    $node->update([
        ...Node::factory()->withAgent()->raw(),
        'agent_public_url' => 'https://agent.example.com',
        'agent_last_seen_at' => now()->subHour(),
        'agent_version' => '0.0.1-stale',
    ]);
    $node->refresh();

    Http::fake(['agent.example.com/api/v1/info' => Http::response([
        'version' => '0.1.0-alpha.1',
        'mode' => 'agent',
        'protocol' => ['min' => AnchorProtocol::VERSION, 'max' => AnchorProtocol::VERSION],
        'capabilities' => ['console.qemu.vnc'],
    ])]);

    $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session",
        ['type' => 'novnc'],
    )->assertCreated();

    // The probe stands in for a heartbeat, so the reported build and
    // capabilities are refreshed too.
    $node->refresh();
    expect($node->agent_version)->toBe('0.1.0-alpha.1')
        ->and($node->agent_capabilities)->toBe(['console.qemu.vnc'])
        ->and($node->agent_last_seen_at->isAfter(now()->subMinute()))->toBeTrue();
});

it('still refuses the session when a stale Anchor cannot be reached either', function () {
    [$user, $_, $node, $server] = createServerModel();
    $node->update([
        ...Node::factory()->withAgent()->raw(),
        'agent_public_url' => 'https://agent.example.com',
        'agent_last_seen_at' => now()->subHour(),
    ]);
    $node->refresh();

    Http::fake(['agent.example.com/api/v1/info' => Http::response(status: 502)]);

    $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session",
        ['type' => 'novnc'],
    )->assertConflict()
        ->assertJsonPath('message', "Anchor {$node->display_name} is not online with a compatible protocol version.");
});

it('does not probe an agent that was never enrolled', function () {
    // An unenrolled agent has no shared secret we could trust, so reaching
    // something at its URL proves nothing. Only a stale heartbeat is probed.
    [$user, $_, $node, $server] = createServerModel();
    $node->update([
        'agent_uuid' => (string) Str::uuid(),
        'agent_secret' => Str::random(64),
        'agent_public_url' => 'https://agent.example.com',
    ]);

    Http::fake();

    $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session",
        ['type' => 'novnc'],
    )->assertConflict();

    Http::assertNothingSent();
});
