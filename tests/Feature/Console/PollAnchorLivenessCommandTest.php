<?php

use App\Models\AnchorEnrollment;
use App\Models\Node;
use App\Models\Relay;
use App\Support\Anchor\AnchorProtocol;
use Illuminate\Support\Facades\Http;

/** A node whose agent last reported in an hour ago. */
function staleNode(string $host, array $attributes = []): Node
{
    [, , $node] = createServerModel();

    $node->update([
        ...Node::factory()->withAgent()->raw(),
        'agent_public_url' => "https://{$host}",
        'agent_last_seen_at' => now()->subHour(),
        ...$attributes,
    ]);

    return $node->refresh();
}

it('probes a node whose agent heartbeat has gone stale', function () {
    $node = staleNode('agent.example.com', ['agent_version' => '0.0.1-stale']);

    Http::fake(['agent.example.com/api/v1/info' => Http::response([
        'version' => '0.1.0-alpha.1',
        'mode' => 'agent',
        'protocol' => ['min' => AnchorProtocol::VERSION, 'max' => AnchorProtocol::VERSION],
        'capabilities' => ['console.qemu.vnc'],
    ])]);

    $this->artisan('anchors:poll')->assertSuccessful();

    $node->refresh();

    expect($node->agent_version)->toBe('0.1.0-alpha.1')
        ->and($node->agent_last_seen_at->isAfter(now()->subMinute()))->toBeTrue()
        // The agent's liveness, never the node's: a running daemon on a host
        // whose Proxmox API is down must not read as a healthy node.
        ->and($node->last_seen_at)->not->toBe($node->agent_last_seen_at);
});

it('probes a relay too', function () {
    $relay = Relay::factory()->enrolled()->create([
        'public_url' => 'https://relay.example.com',
        'last_seen_at' => now()->subHour(),
    ]);

    Http::fake(['relay.example.com/api/v1/info' => Http::response([
        'version' => '0.1.0-alpha.1',
        'mode' => 'relay',
        'protocol' => ['min' => AnchorProtocol::VERSION, 'max' => AnchorProtocol::VERSION],
        'capabilities' => ['console.relay'],
    ])]);

    $this->artisan('anchors:poll')->assertSuccessful();

    expect($relay->refresh()->last_seen_at->isAfter(now()->subMinute()))->toBeTrue();
});

it('leaves installations alone while their heartbeat is still fresh', function () {
    // A recent heartbeat already carries everything a probe would return, so
    // spending a request on it every minute would be pure waste.
    staleNode('agent.example.com', ['agent_last_seen_at' => now()]);

    Http::fake();

    $this->artisan('anchors:poll')->assertSuccessful();

    Http::assertNothingSent();
});

it('never probes a node with no agent installed on it', function () {
    // The v4 shape. There is no secret to trust and nothing listening.
    createServerModel();

    Http::fake();

    $this->artisan('anchors:poll')->assertSuccessful();

    Http::assertNothingSent();
});

it('never probes a machine still waiting to be approved', function () {
    // Nobody has told us an address to probe it at; that is what approval
    // establishes. A probe here could only fail.
    AnchorEnrollment::factory()->create(['last_seen_at' => now()->subHour()]);

    Http::fake();

    $this->artisan('anchors:poll')->assertSuccessful();

    Http::assertNothingSent();
});

it('keeps going when one installation cannot be reached', function () {
    $unreachable = Relay::factory()->enrolled()->create([
        'public_url' => 'https://down.example.com',
        'last_seen_at' => now()->subHour(),
    ]);
    $reachable = Relay::factory()->enrolled()->create([
        'public_url' => 'https://up.example.com',
        'last_seen_at' => now()->subHour(),
    ]);

    Http::fake([
        'down.example.com/*' => Http::response(status: 502),
        'up.example.com/*' => Http::response([
            'version' => '0.1.0-alpha.1',
            'mode' => 'relay',
            'protocol' => ['min' => AnchorProtocol::VERSION, 'max' => AnchorProtocol::VERSION],
            'capabilities' => ['console.relay'],
        ]),
    ]);

    $this->artisan('anchors:poll')->assertSuccessful();

    expect($reachable->refresh()->last_seen_at->isAfter(now()->subMinute()))->toBeTrue()
        ->and($unreachable->refresh()->last_seen_at->isBefore(now()->subMinutes(30)))->toBeTrue();
});
