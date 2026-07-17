<?php

use App\Enums\Node\NodeStatus;
use App\Enums\Node\Testing\ConnectionErrorCode;
use App\Models\Location;
use App\Models\Node;
use App\Services\Nodes\NodeStatusPollService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->node = Node::factory()->for(Location::factory())->create();
    $this->service = app(NodeStatusPollService::class);
});

function pollStatusPayload(): array
{
    return [
        'current-kernel' => ['version' => '#1', 'release' => '6.14.8-2-pve', 'sysname' => 'Linux', 'machine' => 'x86_64'],
        'cpuinfo' => ['cpus' => 32, 'sockets' => 1, 'cores' => 16, 'model' => 'AMD EPYC', 'flags' => ''],
        'cpu' => 0.1,
        'loadavg' => ['0.5', '0.5', '0.5'],
        'memory' => ['used' => 1, 'free' => 1, 'available' => 1, 'total' => 2],
        'swap' => ['used' => 0, 'free' => 0, 'total' => 0],
        'rootfs' => ['used' => 1, 'free' => 1, 'avail' => 1, 'total' => 2],
        'boot-info' => ['mode' => 'efi', 'secureboot' => true],
        'pveversion' => 'pve-manager/9.2.2',
        'uptime' => 10,
    ];
}

it('records a reachable node as online', function () {
    Http::fake(['*/api2/json/nodes/*/status' => Http::response(['data' => pollStatusPayload()], 200)]);

    expect($this->service->handle($this->node))->toBe(NodeStatus::ONLINE);

    $this->node->refresh();
    expect($this->node->status)->toBe(NodeStatus::ONLINE)
        ->and($this->node->status_code)->toBeNull()
        ->and($this->node->last_seen_at)->not->toBeNull()
        ->and($this->node->consecutive_failures)->toBe(0);
});

it('records why an unreachable node failed', function () {
    Http::fake(['*/api2/json/nodes/*/status' => fn () => throw new ConnectionException(
        'cURL error 60: SSL certificate problem: unable to get local issuer certificate',
    )]);

    expect($this->service->handle($this->node))->toBe(NodeStatus::UNREACHABLE);

    $this->node->refresh();
    expect($this->node->status)->toBe(NodeStatus::UNREACHABLE)
        ->and($this->node->status_code)->toBe(ConnectionErrorCode::TLS_ERROR)
        ->and($this->node->status_message)->toContain('SSL certificate problem');
});

it('counts consecutive failures and resets them on recovery', function () {
    // Slice 3 debounces alerting on this counter, so a flap must not reset it
    // and a recovery must. `Http::fake` merges stubs rather than replacing them,
    // so the node's health is flipped through a closure instead.
    $reachable = false;
    Http::fake(['*/api2/json/nodes/*/status' => function () use (&$reachable) {
        if (! $reachable) {
            throw new ConnectionException('Connection refused');
        }

        return Http::response(['data' => pollStatusPayload()], 200);
    }]);

    $this->service->handle($this->node);
    $this->service->handle($this->node->refresh());
    expect($this->node->refresh()->consecutive_failures)->toBe(2);

    $reachable = true;
    $this->service->handle($this->node->refresh());

    expect($this->node->refresh()->consecutive_failures)->toBe(0)
        ->and($this->node->status)->toBe(NodeStatus::ONLINE);
});

it('keeps last_seen_at pointing at the last successful contact', function () {
    $reachable = true;
    Http::fake(['*/api2/json/nodes/*/status' => function () use (&$reachable) {
        if (! $reachable) {
            throw new ConnectionException('Connection refused');
        }

        return Http::response(['data' => pollStatusPayload()], 200);
    }]);

    $this->service->handle($this->node);
    $seenAt = $this->node->refresh()->last_seen_at;

    $this->travel(10)->minutes();

    $reachable = false;
    $this->service->handle($this->node->refresh());

    // A failed check must not advance it -- staleness is measured from the last
    // time the node actually answered.
    expect($this->node->refresh()->last_seen_at->timestamp)->toBe($seenAt->timestamp)
        ->and($this->node->status_checked_at->timestamp)->toBeGreaterThan($seenAt->timestamp);
});

it('degrades a remembered status to unknown once it goes stale', function () {
    Http::fake(['*/api2/json/nodes/*/status' => Http::response(['data' => pollStatusPayload()], 200)]);
    $this->service->handle($this->node);

    expect($this->node->refresh()->currentStatus())->toBe(NodeStatus::ONLINE);

    // Nothing polled since -- the scheduler or the queue has stopped. Reporting
    // the remembered `online` here would be reporting the past as the present.
    $this->travel(Node::STATUS_TTL_MINUTES + 1)->minutes();

    expect($this->node->refresh()->currentStatus())->toBe(NodeStatus::UNKNOWN)
        ->and($this->node->status)->toBe(NodeStatus::ONLINE);
});

it('reads unknown before it has ever been polled', function () {
    expect($this->node->currentStatus())->toBe(NodeStatus::UNKNOWN);
});
