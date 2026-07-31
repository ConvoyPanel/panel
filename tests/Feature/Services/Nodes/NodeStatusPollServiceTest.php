<?php

use App\Data\Admin\Overview\NodeResourceSnapshotData;
use App\Enums\Node\ConnectionErrorCode;
use App\Enums\Node\NodeStatus;
use App\Enums\Server\State;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Services\Nodes\GuestStateCache;
use App\Services\Nodes\NodeResourceSnapshotCache;
use App\Services\Nodes\NodeStatusPollService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // The array cache is not torn down between tests, but the database is --
    // so node ids restart and a previous test's `node:1:vm-states` is still
    // sitting there under the new node's key. Without this flush, a test that
    // never polls anything reads the last test's guests.
    Cache::flush();

    $this->node = Node::factory()->for(Location::factory())->create();
    $this->service = app(NodeStatusPollService::class);
});

/** One `type=qemu` row as /cluster/resources returns it. */
function guestRow(int $vmid, string $status, string $nodeName): array
{
    return [
        'type' => 'qemu',
        'id' => "qemu/{$vmid}",
        'name' => "vm-{$vmid}",
        'status' => $status,
        'vmid' => $vmid,
        'node' => $nodeName,
        'maxcpu' => 2,
        'cpu' => 0.1,
        'maxmem' => 2048,
        'mem' => 1024,
        'maxdisk' => 100,
        'disk' => 50,
        'uptime' => 10,
    ];
}

/**
 * A /cluster/resources body. The node/storage rows are included because the
 * real endpoint returns them and the poller has to tolerate them.
 */
function pollStatusPayload(array $guests = [], array $nodes = []): array
{
    return [
        ...($nodes ?: [['type' => 'node', 'id' => 'node/pve', 'node' => 'pve', 'status' => 'online']]),
        ['type' => 'storage', 'id' => 'storage/pve/local', 'node' => 'pve', 'status' => 'available'],
        ...$guests,
    ];
}

function nodeResourceRow(string $nodeName, array $overrides = []): array
{
    return [
        'type' => 'node',
        'id' => "node/{$nodeName}",
        'node' => $nodeName,
        'status' => 'online',
        'maxcpu' => 16,
        'cpu' => 0.25,
        'maxmem' => 16 * 1024 * 1024 * 1024,
        'mem' => 4 * 1024 * 1024 * 1024,
        'maxdisk' => 256 * 1024 * 1024 * 1024,
        'disk' => 64 * 1024 * 1024 * 1024,
        'uptime' => 86400,
        ...$overrides,
    ];
}

it('records a reachable node as online', function () {
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    expect($this->service->handle($this->node))->toBe(NodeStatus::ONLINE);

    $this->node->refresh();
    expect($this->node->status)->toBe(NodeStatus::ONLINE)
        ->and($this->node->status_code)->toBeNull()
        ->and($this->node->last_seen_at)->not->toBeNull()
        ->and($this->node->consecutive_failures)->toBe(0);
});

it('records why an unreachable node failed', function () {
    Http::fake(['*/api2/json/cluster/resources' => fn () => throw new ConnectionException(
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
    Http::fake(['*/api2/json/cluster/resources' => function () use (&$reachable) {
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
    Http::fake(['*/api2/json/cluster/resources' => function () use (&$reachable) {
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
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);
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

it('records each guest power state from the same response', function () {
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload([
        guestRow(100, 'running', $this->node->name),
        guestRow(101, 'stopped', $this->node->name),
    ])], 200)]);

    $this->service->handle($this->node);

    expect(app(GuestStateCache::class)->for($this->node))
        ->toBe([100 => 'running', 101 => 'stopped']);
});

it('records current resources for this node from the same response', function () {
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        guests: [],
        nodes: [
            nodeResourceRow('some-other-node', ['cpu' => 0.9]),
            nodeResourceRow($this->node->name),
        ],
    )], 200)]);

    $this->service->handle($this->node);

    $snapshot = app(NodeResourceSnapshotCache::class)->for($this->node);

    expect($snapshot)->toBeInstanceOf(NodeResourceSnapshotData::class)
        ->and($snapshot->cpu->count)->toBe(16)
        ->and($snapshot->cpu->percent)->toBe(25.0)
        ->and($snapshot->memory->used)->toBe(4 * 1024 * 1024 * 1024)
        ->and($snapshot->memory->percent)->toBe(25.0)
        ->and($snapshot->disk->used)->toBe(64 * 1024 * 1024 * 1024)
        ->and($snapshot->disk->percent)->toBe(25.0)
        ->and($snapshot->uptimeInSeconds)->toBe(86400);
});

it('leaves the last resource snapshot alone when a poll fails', function () {
    $reachable = true;
    Http::fake(['*/api2/json/cluster/resources' => function () use (&$reachable) {
        if (! $reachable) {
            throw new ConnectionException('Connection refused');
        }

        return Http::response(['data' => pollStatusPayload(
            nodes: [nodeResourceRow($this->node->name)],
        )], 200);
    }]);

    $this->service->handle($this->node);
    $observedAt = app(NodeResourceSnapshotCache::class)->for($this->node)->observedAt;

    $reachable = false;
    $this->service->handle($this->node->refresh());

    expect(app(NodeResourceSnapshotCache::class)->for($this->node)->observedAt)
        ->toEqual($observedAt);
});

it('ignores guests belonging to another node in the cluster', function () {
    // /cluster/resources answers for every member. vmids are unique per cluster,
    // not per node, so an unfiltered map silently attributes a neighbour's guest
    // to this node -- and the number looks perfectly plausible.
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload([
        guestRow(100, 'running', $this->node->name),
        guestRow(200, 'running', 'some-other-node'),
    ])], 200)]);

    $this->service->handle($this->node);

    expect(app(GuestStateCache::class)->for($this->node))->toBe([100 => 'running']);
});

it('leaves the last known guest states alone when a poll fails', function () {
    $reachable = true;
    Http::fake(['*/api2/json/cluster/resources' => function () use (&$reachable) {
        if (! $reachable) {
            throw new ConnectionException('Connection refused');
        }

        return Http::response(['data' => pollStatusPayload([
            guestRow(100, 'running', $this->node->name),
        ])], 200);
    }]);

    $this->service->handle($this->node);

    $reachable = false;
    $this->service->handle($this->node->refresh());

    // One failed poll is not evidence a guest changed state. The map stands
    // until it expires on its own.
    expect(app(GuestStateCache::class)->for($this->node))->toBe([100 => 'running']);
});

it('says unknown rather than stopped for a guest it cannot vouch for', function () {
    $server = Server::factory()->for($this->node)->create(['vmid' => 100]);
    $cache = app(GuestStateCache::class);

    // Never polled.
    expect($cache->stateFor($server))->toBeNull();

    // Polled, but PVE did not mention this guest -- usually removed outside
    // Convoy. Answering `stopped` would invite someone to press Start on it.
    $cache->put($this->node, [999 => 'running']);
    expect($cache->stateFor($server))->toBeNull();

    $cache->put($this->node, [100 => 'running']);
    expect($cache->stateFor($server))->toBe(State::RUNNING);
});

it('expires the guest map with the node status it was observed alongside', function () {
    // Asserted on the expiry handed to the cache rather than by travelling past
    // it: `ArrayStore` (the test driver) expires entries against `microtime()`,
    // so `$this->travel()` -- which only moves Carbon's clock -- never ages a
    // cache entry out. The node-status assertions above work because those read
    // `status_checked_at` through Carbon.
    //
    // Time is frozen so the expiry the service computes and the one asserted
    // here are the same instant rather than microseconds apart.
    $this->freezeTime();
    Cache::spy();

    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload([
        guestRow(100, 'running', $this->node->name),
    ])], 200)]);

    $this->service->handle($this->node);

    // Both facts come from one response, so neither may outlive the other: a
    // node reading `online` beside guests reading `unknown` is unreadable.
    Cache::shouldHaveReceived('put')->once()->withArgs(
        fn (string $key, array $value, $expiry) => $key === "node:{$this->node->id}:vm-states"
            && $value['states'] === [100 => 'running']
            && $value['observed_at'] === now()->getTimestampMs()
            && $expiry->equalTo(now()->addMinutes(Node::STATUS_TTL_MINUTES))
    );
});
