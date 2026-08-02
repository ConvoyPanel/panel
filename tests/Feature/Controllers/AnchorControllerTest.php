<?php

use App\Enums\Anchor\AnchorCompatibility;
use App\Models\Anchor;
use App\Models\User;
use App\Settings\AnchorSettings;

it('issues and consumes a one-time enrollment token', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $anchor = Anchor::factory()->create();

    $enrollment = $this->actingAs($admin)
        ->postJson("/api/admin/anchors/{$anchor->id}/enrollment")
        ->assertCreated()
        ->assertJsonStructure(['data' => ['token', 'command', 'expiresAt']]);

    $token = $enrollment->json('data.token');
    $response = $this->postJson('/api/anchor/enroll', ['token' => $token]);

    $response->assertOk()
        ->assertJsonPath('config.mode', 'agent')
        ->assertJsonPath('config.installation_id', $anchor->uuid)
        ->assertJsonPath('config.listen_addr', '127.0.0.1:2115');

    $anchor->refresh();
    expect($response->json('config.secret'))->toBe($anchor->secret)
        ->and($anchor->enrolled_at)->not->toBeNull()
        ->and($anchor->enrollment_token_hash)->toBeNull();

    $this->postJson('/api/anchor/enroll', ['token' => $token])
        ->assertUnprocessable();
});

it('authenticates heartbeats and records compatibility data', function () {
    $anchor = Anchor::factory()->create(['enrolled_at' => now()]);
    $payload = [
        'version' => '0.1.0-alpha.1',
        'mode' => 'agent',
        'protocol' => ['min' => 1, 'max' => 1],
        'capabilities' => ['console.qemu.vnc'],
    ];

    $this->withToken("{$anchor->uuid}.{$anchor->secret}")
        ->postJson('/api/anchor/heartbeat', $payload)
        ->assertNoContent();

    $anchor->refresh();
    expect($anchor->version)->toBe('0.1.0-alpha.1')
        ->and($anchor->capabilities)->toBe(['console.qemu.vnc'])
        ->and($anchor->compatibility())->toBe(AnchorCompatibility::COMPATIBLE);

    $this->withToken("{$anchor->uuid}.wrong-secret")
        ->postJson('/api/anchor/heartbeat', $payload)
        ->assertUnauthorized();
});

it('creates agents and attaches nodes through the admin API', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    [, , $node] = createServerModel();

    $response = $this->actingAs($admin)->postJson('/api/admin/anchors', [
        'name' => 'Primary agent',
        'mode' => 'agent',
        'public_url' => 'https://anchor.example.com',
        'node_ids' => [$node->id],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Primary agent')
        ->assertJsonPath('data.nodesCount', 1)
        ->assertJsonPath('data.compatibility', 'unenrolled');
    expect($node->refresh()->anchor_id)->toBe($response->json('data.id'));
});

it('resolves the panel URL an Anchor is given from the override cascade', function () {
    config(['app.url' => 'https://panel.example.com']);
    $admin = User::factory()->create(['root_admin' => true]);
    $anchor = Anchor::factory()->create(['panel_url_override' => null]);

    $enrollUrl = function () use ($admin, $anchor) {
        $anchor->refresh();

        return $this->actingAs($admin)
            ->postJson("/api/admin/anchors/{$anchor->id}/enrollment")
            ->json('data.command');
    };

    // Nothing set: the panel's own address, as before.
    expect($enrollUrl())->toContain('--panel-url https://panel.example.com');

    // A panel-wide default covers a fleet that all needs the same address.
    app(AnchorSettings::class)->fill(['panel_url' => 'https://panel.internal'])->save();
    expect($enrollUrl())->toContain('--panel-url https://panel.internal');

    // A single Anchor on a different network still wins over that default.
    $anchor->update(['panel_url_override' => 'https://panel.tailnet.ts.net']);
    expect($enrollUrl())->toContain('--panel-url https://panel.tailnet.ts.net');

    // And whatever the command shows is what the agent is told to store, so
    // the two can never disagree.
    $token = $this->actingAs($admin)
        ->postJson("/api/admin/anchors/{$anchor->id}/enrollment")
        ->json('data.token');

    $this->postJson('/api/anchor/enroll', ['token' => $token])
        ->assertOk()
        ->assertJsonPath('config.panel_url', 'https://panel.tailnet.ts.net/');
});
