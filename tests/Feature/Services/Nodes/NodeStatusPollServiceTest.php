<?php

use App\Data\Admin\Overview\NodeResourceSnapshotData;
use App\Enums\Node\ConnectionErrorCode;
use App\Enums\Node\NodeStatus;
use App\Enums\Server\PowerState;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Services\Nodes\GuestStateCache;
use App\Services\Nodes\NodeResourceSnapshotCache;
use App\Services\Nodes\NodeStatusPollService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** A standalone host: `/cluster/status` lists members but carries no `type=cluster` row. */
const STANDALONE_STATUS = [['type' => 'node', 'name' => 'pve', 'local' => 1, 'online' => 1]];

beforeEach(function () {
    // The array cache is not torn down between tests, but the database is --
    // so node ids restart and a previous test's `node:1:vm-states` is still
    // sitting there under the new node's key. Without this flush, a test that
    // never polls anything reads the last test's guests.
    Cache::flush();

    $this->node = Node::factory()->for(Location::factory())->create();
    $this->service = app(NodeStatusPollService::class);

    /*
     * Http::fake merges stubs and the first registered match wins, so a later
     * call cannot replace this one. The stub reads the property instead, and a
     * test that wants a clustered host just assigns to it.
     */
    $this->clusterStatus = STANDALONE_STATUS;
    Http::fake([
        '*/api2/json/cluster/status' => function () {
            // `false` means "this endpoint is down"; anything else is a body.
            if ($this->clusterStatus === false) {
                throw new ConnectionException('refused');
            }

            return Http::response(['data' => $this->clusterStatus], 200);
        },
    ]);
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

/** One `type=storage` row as /cluster/resources returns it. */
function storageRow(
    string $name,
    string $nodeName,
    int $used = 0,
    int $total = 0,
    string $status = 'available',
    bool $shared = false,
    ?string $pluginType = 'dir',
    ?string $content = 'images,rootdir',
): array {
    return [
        'type' => 'storage',
        'id' => "storage/{$nodeName}/{$name}",
        'storage' => $name,
        'node' => $nodeName,
        'status' => $status,
        'shared' => $shared,
        'disk' => $used,
        'maxdisk' => $total,
        'plugintype' => $pluginType,
        'content' => $content,
    ];
}

/**
 * A /cluster/resources body. The node/storage rows are included because the
 * real endpoint returns them and the poller has to tolerate them.
 */
function pollStatusPayload(array $guests = [], array $nodes = [], array $storages = []): array
{
    return [
        ...($nodes ?: [['type' => 'node', 'id' => 'node/pve', 'node' => 'pve', 'status' => 'online']]),
        ...($storages ?: [['type' => 'storage', 'id' => 'storage/pve/local', 'node' => 'pve', 'status' => 'available']]),
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

it('records every datastore on this node, fullest first', function () {
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [
            storageRow('local', $this->node->name, used: 10, total: 100),
            storageRow('local-lvm', $this->node->name, used: 90, total: 100),
            storageRow('nfs-backups', $this->node->name, used: 50, total: 100, shared: true),
        ],
    )], 200)]);

    $this->service->handle($this->node);

    $datastores = app(NodeResourceSnapshotCache::class)->for($this->node)->datastores;

    // Sorted by fullness, not by the order PVE happened to list them: the store
    // about to run out is the one worth reading first.
    expect($datastores)->toHaveCount(3)
        ->and(array_map(fn ($datastore) => $datastore->name, $datastores->items()))
        ->toBe(['local-lvm', 'nfs-backups', 'local'])
        ->and($datastores[0]->usage->percent)->toBe(90.0)
        ->and($datastores[0]->usage->used)->toBe(90)
        ->and($datastores[0]->usage->total)->toBe(100)
        ->and($datastores[0]->online)->toBeTrue()
        ->and($datastores[1]->shared)->toBeTrue()
        ->and($datastores[2]->shared)->toBeFalse();
});

it('ignores datastores belonging to another node in the cluster', function () {
    // Same trap as the guest map: /cluster/resources answers for every member,
    // and a shared store is reported once per node that mounts it. Unfiltered,
    // a neighbour's datastores are attributed to this node.
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [
            storageRow('mine', $this->node->name, used: 1, total: 10),
            storageRow('theirs', 'some-other-node', used: 9, total: 10),
        ],
    )], 200)]);

    $this->service->handle($this->node);

    expect(array_map(
        fn ($datastore) => $datastore->name,
        app(NodeResourceSnapshotCache::class)->for($this->node)->datastores->items(),
    ))->toBe(['mine']);
});

it('sums the datastores into one storage figure for the dashboard', function () {
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [
            storageRow('local', $this->node->name, used: 10, total: 100),
            storageRow('local-lvm', $this->node->name, used: 40, total: 100),
            // Excluded from the sum: it reports zeroes because it is unmounted,
            // and counting it would deflate the percentage of everything else.
            storageRow('nfs-dead', $this->node->name, status: 'unknown'),
        ],
    )], 200)]);

    $this->service->handle($this->node);

    $snapshot = app(NodeResourceSnapshotCache::class)->for($this->node);

    expect($snapshot->storage->used)->toBe(50)
        ->and($snapshot->storage->total)->toBe(200)
        ->and($snapshot->storage->percent)->toBe(25.0)
        ->and($snapshot->datastoreCount)->toBe(3)
        ->and($snapshot->unreadableDatastores)->toBe(1);
});

it('reports zero storage rather than dividing by zero when nothing is readable', function () {
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [storageRow('nfs-dead', $this->node->name, status: 'unknown')],
    )], 200)]);

    $this->service->handle($this->node);

    $snapshot = app(NodeResourceSnapshotCache::class)->for($this->node);

    expect($snapshot->storage->total)->toBe(0)
        ->and($snapshot->storage->percent)->toBe(0.0)
        ->and($snapshot->unreadableDatastores)->toBe(1);
});

it('marks a datastore it could not read as offline rather than empty', function () {
    // An unmounted NFS export still appears in the listing, reporting zeroes.
    // "0% used" is a comfortable lie; the row has to say it is unreadable.
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [storageRow('nfs-dead', $this->node->name, status: 'unknown')],
    )], 200)]);

    $this->service->handle($this->node);

    $datastores = app(NodeResourceSnapshotCache::class)->for($this->node)->datastores;

    expect($datastores)->toHaveCount(1)
        ->and($datastores[0]->online)->toBeFalse()
        ->and($datastores[0]->usage->percent)->toBe(0.0);
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
    expect($cache->stateFor($server))->toBe(PowerState::RUNNING);
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

it('records what Proxmox says about a registered storage', function () {
    $storage = Storage::factory()->create(['name' => 'local-lvm']);
    $this->node->storages()->attach($storage);

    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [storageRow(
            'local-lvm',
            $this->node->name,
            used: 900,
            total: 1000,
            shared: false,
            pluginType: 'lvmthin',
            content: 'images,rootdir',
        )],
    )], 200)]);

    $this->service->handle($this->node);

    expect($storage->refresh())
        ->pve_type->toBe('lvmthin')
        ->pve_shared->toBeFalse()
        ->pve_content->toBe('images,rootdir')
        ->discovered_total->toBe(1000)
        ->discovered_used->toBe(900)
        ->and($storage->discovered_at)->not->toBeNull();
});

it('adopts the content types Proxmox reports, correcting a stale answer', function () {
    // The flags used to be tick boxes on the edit form, so a storage could carry
    // an answer PVE disagreed with -- here, one that has since stopped accepting
    // backups and started accepting containers. The poll settles it.
    $storage = Storage::factory()->create([
        'name' => 'local-lvm',
        'stores_kvm' => true,
        'stores_lxc' => false,
        'stores_backups' => true,
    ]);
    $this->node->storages()->attach($storage);

    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [storageRow(
            'local-lvm',
            $this->node->name,
            used: 900,
            total: 1000,
            shared: false,
            pluginType: 'lvmthin',
            content: 'images,rootdir',
        )],
    )], 200)]);

    $this->service->handle($this->node);

    expect($storage->refresh())
        ->stores_kvm->toBeTrue()
        ->stores_lxc->toBeTrue()
        ->stores_backups->toBeFalse()
        ->stores_lxc_templates->toBeFalse()
        ->stores_iso->toBeFalse()
        ->stores_snippets->toBeFalse();
});

it('leaves the content flags alone when the row carried no content list', function () {
    // No list is a report that did not say, not a storage that holds nothing.
    // Deriving from it would strip every flag and quietly take the storage out
    // of every allocation the panel offers.
    $storage = Storage::factory()->create([
        'name' => 'quiet-pool',
        'stores_kvm' => true,
        'stores_backups' => true,
    ]);
    $this->node->storages()->attach($storage);

    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [storageRow(
            'quiet-pool',
            $this->node->name,
            used: 10,
            total: 100,
            shared: false,
            pluginType: 'dir',
            content: null,
        )],
    )], 200)]);

    $this->service->handle($this->node);

    expect($storage->refresh())
        ->stores_kvm->toBeTrue()
        ->stores_backups->toBeTrue();
});

it('identifies a Proxmox Backup Server datastore by its type alone', function () {
    // PBS needs no model of its own: it arrives as an ordinary storage row whose
    // plugintype says `pbs`, marked shared by PVE, carrying backups only.
    $storage = Storage::factory()->create(['name' => 'pbs-main']);
    $this->node->storages()->attach($storage);

    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [storageRow(
            'pbs-main',
            $this->node->name,
            used: 4_000,
            total: 20_000,
            shared: true,
            pluginType: 'pbs',
            content: 'backup',
        )],
    )], 200)]);

    $this->service->handle($this->node);

    expect($storage->refresh())
        ->pve_type->toBe('pbs')
        ->pve_shared->toBeTrue()
        ->pve_content->toBe('backup');
});

it('keeps the last known capacity when a storage stops reporting', function () {
    $storage = Storage::factory()->create(['name' => 'nfs-flaky']);
    $this->node->storages()->attach($storage);

    $reporting = true;
    Http::fake(['*/api2/json/cluster/resources' => function () use (&$reporting) {
        return Http::response(['data' => pollStatusPayload(
            nodes: [nodeResourceRow($this->node->name)],
            storages: [storageRow(
                'nfs-flaky',
                $this->node->name,
                used: 500,
                total: 1000,
                status: $reporting ? 'available' : 'unknown',
            )],
        )], 200);
    }]);

    $this->service->handle($this->node);
    expect($storage->refresh()->discovered_used)->toBe(500);

    // PVE could not read it this time round. Blanking the figures would turn a
    // brief outage into "capacity unknown" everywhere it is shown.
    $reporting = false;
    $this->service->handle($this->node->refresh());

    expect($storage->refresh()->discovered_used)->toBe(500)
        ->and($storage->discovered_total)->toBe(1000);
});

it('leaves storages registered on another node alone', function () {
    $mine = Storage::factory()->create(['name' => 'shared-name']);
    $theirs = Storage::factory()->create(['name' => 'shared-name']);
    $otherNode = Node::factory()->for(Location::factory())->create();
    $this->node->storages()->attach($mine);
    $otherNode->storages()->attach($theirs);

    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [storageRow('shared-name', $this->node->name, used: 10, total: 100)],
    )], 200)]);

    $this->service->handle($this->node);

    // Same PVE name, different node, different row -- which is exactly the
    // duplication cluster-wide identity is meant to collapse later.
    expect($mine->refresh()->discovered_used)->toBe(10)
        ->and($theirs->refresh()->discovered_used)->toBeNull();
});

it('records the PVE cluster a node belongs to', function () {
    $this->clusterStatus = [
        ['type' => 'cluster', 'name' => 'prod-cluster', 'nodes' => 3, 'quorate' => 1],
        ...STANDALONE_STATUS,
    ];
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);

    expect($this->node->refresh()->cluster_name)->toBe('prod-cluster');
});

it('reads a standalone host as belonging to no cluster', function () {
    // No `type=cluster` row is how PVE says "not clustered" -- not an error.
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);

    expect($this->node->refresh()->cluster_name)->toBeNull();
});

it('keeps the known cluster when only the cluster lookup fails', function () {
    $this->clusterStatus = [
        ['type' => 'cluster', 'name' => 'prod-cluster', 'nodes' => 3, 'quorate' => 1],
        ...STANDALONE_STATUS,
    ];
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);
    $this->service->handle($this->node);

    // The node itself is demonstrably up -- /cluster/resources just answered --
    // so a failure on the second call must not erase what we already knew.
    $this->clusterStatus = false;
    $this->service->handle($this->node->refresh());

    expect($this->node->refresh()->cluster_name)->toBe('prod-cluster')
        ->and($this->node->status)->toBe(NodeStatus::ONLINE);
});

it('does not ask for the cluster when the node is unreachable', function () {
    Http::fake(['*/api2/json/cluster/resources' => fn () => throw new ConnectionException('refused')]);

    $this->service->handle($this->node);

    // The whole point of ordering it second: a down node must not cost two timeouts.
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/cluster/status'));
});

it('attaches a shared pool to every clustered node Proxmox reports it on', function () {
    $peer = Node::factory()->for(Location::factory())->create([
        'name' => 'pve-2',
        'cluster_name' => 'prod',
    ]);
    $storage = Storage::factory()->create(['name' => 'ceph-vm']);
    $this->node->storages()->attach($storage);

    $this->clusterStatus = [
        ['type' => 'cluster', 'name' => 'prod', 'nodes' => 2, 'quorate' => 1],
        ...STANDALONE_STATUS,
    ];
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [
            storageRow('ceph-vm', $this->node->name, used: 1, total: 10, shared: true, pluginType: 'rbd'),
            storageRow('ceph-vm', 'pve-2', used: 1, total: 10, shared: true, pluginType: 'rbd'),
        ],
    )], 200)]);

    $this->service->handle($this->node);

    // Which nodes reach a pool is Proxmox's answer, not something to ask for.
    // Both sides are sorted: the polled node's name comes from a factory, so it
    // collates either side of 'pve-2' depending on the run.
    expect($storage->refresh()->nodes()->pluck('name')->sort()->values()->all())
        ->toBe(collect([$this->node->name, 'pve-2'])->sort()->values()->all());
});

it('never links a local storage that merely shares a name', function () {
    $peer = Node::factory()->for(Location::factory())->create([
        'name' => 'pve-2',
        'cluster_name' => 'prod',
    ]);
    $storage = Storage::factory()->create(['name' => 'local-lvm']);
    $this->node->storages()->attach($storage);

    $this->clusterStatus = [
        ['type' => 'cluster', 'name' => 'prod', 'nodes' => 2, 'quorate' => 1],
        ...STANDALONE_STATUS,
    ];
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [
            storageRow('local-lvm', $this->node->name, used: 1, total: 10, shared: false, pluginType: 'lvmthin'),
            storageRow('local-lvm', 'pve-2', used: 5, total: 10, shared: false, pluginType: 'lvmthin'),
        ],
    )], 200)]);

    $this->service->handle($this->node);

    // `local-lvm` exists on every host under one cluster-wide definition, but
    // each is a physically different disk. Linking them would claim one pool
    // where there are two.
    expect($storage->refresh()->nodes()->count())->toBe(1)
        ->and($peer->storages()->count())->toBe(0);
});

it('does not link a shared pool onto a node in another cluster', function () {
    $stranger = Node::factory()->for(Location::factory())->create([
        'name' => 'pve-2',
        'cluster_name' => 'somewhere-else',
    ]);
    $storage = Storage::factory()->create(['name' => 'ceph-vm']);
    $this->node->storages()->attach($storage);

    $this->clusterStatus = [
        ['type' => 'cluster', 'name' => 'prod', 'nodes' => 2, 'quorate' => 1],
        ...STANDALONE_STATUS,
    ];
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [
            storageRow('ceph-vm', 'pve-2', used: 1, total: 10, shared: true, pluginType: 'rbd'),
        ],
    )], 200)]);

    $this->service->handle($this->node);

    expect($stranger->storages()->count())->toBe(0);
});

it('links nothing for a standalone host', function () {
    $storage = Storage::factory()->create(['name' => 'ceph-vm']);
    $this->node->storages()->attach($storage);

    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [storageRow('ceph-vm', $this->node->name, used: 1, total: 10, shared: true)],
    )], 200)]);

    $this->service->handle($this->node);

    expect($storage->refresh()->nodes()->count())->toBe(1);
});
