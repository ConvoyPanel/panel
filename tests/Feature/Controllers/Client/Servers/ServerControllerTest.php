<?php

use App\Enums\Anchor\AnchorMode;
use App\Models\Anchor;
use App\Models\User;
use App\Services\Api\JWTService;
use Illuminate\Support\Facades\Http;

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
        ->assertJsonPath('data.type', 'novnc');

    $token = app(JWTService::class)->decode($anchor->secret, $response->json('data.token'));
    expect($token->isPermittedFor($anchor->uuid))->toBeTrue()
        ->and($token->isRelatedTo($user->uuid))->toBeTrue()
        ->and($token->claims()->get('console'))->toBe([
            'type' => 'qemu_vnc',
            'vm_id' => $server->vmid,
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
        ->assertJsonPath('data.url', 'wss://relay.example.com/api/v1/console');

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
