<?php

use App\Data\Admin\Overview\NodeResourceSnapshotData;
use App\Enums\Node\ConnectionErrorCode;
use App\Enums\Node\NodeStatus;
use App\Enums\Server\PowerState;
use App\Models\Cluster;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Models\StorageToNode;
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

    // The cluster CA fingerprint, fetched only when identity is in question.
    // `false` means "this endpoint is down", as above.
    $this->caFingerprint = 'DE:FA:00';
    Http::fake([
        '*/api2/json/nodes/*/certificates/info' => function () {
            if ($this->caFingerprint === false) {
                throw new ConnectionException('refused');
            }

            return Http::response(['data' => [
                ['filename' => 'pve-ssl.pem', 'fingerprint' => '55:1e'],
                ['filename' => 'pve-root-ca.pem', 'fingerprint' => $this->caFingerprint],
            ]], 200);
        },
    ]);
});

/** A clustered host's /cluster/status: the cluster row plus one row per member. */
function clusteredStatus(string $name, array $members): array
{
    return [
        ['type' => 'cluster', 'name' => $name, 'nodes' => count($members), 'quorate' => 1],
        ...array_map(
            fn (string $member) => ['type' => 'node', 'name' => $member, 'online' => 1],
            $members,
        ),
    ];
}

/** The (storage, node) link, where the per-node figures now live. */
function linkFor(Node $node, Storage $storage): ?StorageToNode
{
    return StorageToNode::query()
        ->where('storage_id', $storage->id)
        ->where('node_id', $node->id)
        ->first();
}

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

    // What the storage *is* lands on the definition; how full it is lands on
    // this node's link, because capacity is observed per mount.
    expect($storage->refresh())
        ->pve_type->toBe('lvmthin')
        ->pve_shared->toBeFalse()
        ->pve_content->toBe('images,rootdir');

    $link = linkFor($this->node, $storage);
    expect($link->discovered_total)->toBe(1000)
        ->and($link->discovered_used)->toBe(900)
        ->and($link->discovered_at)->not->toBeNull();
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
    expect(linkFor($this->node, $storage)->discovered_used)->toBe(500);

    // PVE could not read it this time round. Blanking the figures would turn a
    // brief outage into "capacity unknown" everywhere it is shown.
    $reporting = false;
    $this->service->handle($this->node->refresh());

    $link = linkFor($this->node, $storage);
    expect($link->discovered_used)->toBe(500)
        ->and($link->discovered_total)->toBe(1000);
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

    // Same PVE name on two standalone hosts is two definitions, and a poll of
    // one host may only ever write its own figures.
    expect(linkFor($this->node, $mine)->discovered_used)->toBe(10)
        ->and(linkFor($otherNode, $theirs)->discovered_used)->toBeNull();
});

it('records the PVE cluster a node belongs to, identified by its CA', function () {
    $this->clusterStatus = clusteredStatus('prod-cluster', [$this->node->name]);
    $this->caFingerprint = 'AA:BB:CC';
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);

    $cluster = $this->node->refresh()->cluster;
    expect($cluster)->not->toBeNull()
        ->and($cluster->fingerprint)->toBe('AA:BB:CC')
        ->and($cluster->name)->toBe('prod-cluster')
        ->and($cluster->member_names)->toBe([$this->node->name]);
});

it('never confuses two clusters that share a name', function () {
    // Somebody else's cluster, also called `proxmox`. The name used to be the
    // identity, which glued these together and offered pools across them.
    $other = Cluster::factory()->create([
        'name' => 'proxmox',
        'fingerprint' => '11:11',
        'member_names' => ['their-pve'],
    ]);

    $this->clusterStatus = clusteredStatus('proxmox', [$this->node->name]);
    $this->caFingerprint = '22:22';
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);

    $cluster = $this->node->refresh()->cluster;
    expect($cluster->id)->not->toBe($other->id)
        ->and($cluster->fingerprint)->toBe('22:22')
        ->and($other->refresh()->member_names)->toBe(['their-pve']);
});

it('gives a standalone host its own scope without asking for a certificate', function () {
    // No `type=cluster` row is how PVE says "not clustered" -- not an error.
    // Standalone scopes are keyed to the node, never to its CA: a node
    // separated from a cluster without a reinstall still carries the old
    // cluster's certificate, and keying by it would glue it back on.
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);

    $cluster = $this->node->refresh()->cluster;
    expect($cluster)->not->toBeNull()
        ->and($cluster->fingerprint)->toBeNull()
        ->and($cluster->name)->toBeNull()
        ->and($cluster->member_names)->toBe([$this->node->name]);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/certificates/info'));
});

it('keeps the known cluster when only the cluster lookup fails', function () {
    $this->clusterStatus = clusteredStatus('prod-cluster', [$this->node->name]);
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);
    $this->service->handle($this->node);

    $clusterId = $this->node->refresh()->cluster_id;

    // The node itself is demonstrably up -- /cluster/resources just answered --
    // so a failure on the second call must not erase what we already knew.
    $this->clusterStatus = false;
    $this->service->handle($this->node->refresh());

    expect($this->node->refresh()->cluster_id)->toBe($clusterId)
        ->and($this->node->cluster->name)->toBe('prod-cluster')
        ->and($this->node->status)->toBe(NodeStatus::ONLINE);
});

it('leaves a node unresolved when the certificate lookup fails', function () {
    $this->clusterStatus = clusteredStatus('prod-cluster', [$this->node->name]);
    $this->caFingerprint = false;
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);

    // Unidentified is a safe state -- nothing links, nothing merges -- and the
    // node is still demonstrably up.
    expect($this->node->refresh()->cluster_id)->toBeNull()
        ->and($this->node->status)->toBe(NodeStatus::ONLINE);
});

it('does not refetch the certificate once the cluster is known', function () {
    $this->clusterStatus = clusteredStatus('prod-cluster', [$this->node->name]);
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);
    $this->service->handle($this->node->refresh());

    // Identity questions are only asked when something suggests the answer
    // moved; the steady-state poll stays at its usual request count.
    Http::assertSentCount(5); // 2 x (resources + status) + 1 x certificates
});

it('does not ask for the cluster when the node is unreachable', function () {
    Http::fake(['*/api2/json/cluster/resources' => fn () => throw new ConnectionException('refused')]);

    $this->service->handle($this->node);

    // The whole point of ordering it second: a down node must not cost two timeouts.
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/cluster/status'));
});

it('attaches a shared pool to every clustered node Proxmox reports it on', function () {
    $cluster = Cluster::factory()->create([
        'name' => 'prod',
        'fingerprint' => 'FF:00',
        'member_names' => [$this->node->name, 'pve-2'],
    ]);
    $peer = Node::factory()->for(Location::factory())->create([
        'name' => 'pve-2',
        'cluster_id' => $cluster->id,
    ]);
    $storage = Storage::factory()->create(['name' => 'ceph-vm', 'cluster_id' => $cluster->id]);
    $this->node->storages()->attach($storage);

    $this->clusterStatus = clusteredStatus('prod', [$this->node->name, 'pve-2']);
    $this->caFingerprint = 'FF:00';
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
    $cluster = Cluster::factory()->create([
        'name' => 'prod',
        'fingerprint' => 'FF:00',
        'member_names' => [$this->node->name, 'pve-2'],
    ]);
    $peer = Node::factory()->for(Location::factory())->create([
        'name' => 'pve-2',
        'cluster_id' => $cluster->id,
    ]);
    $storage = Storage::factory()->create(['name' => 'local-lvm', 'cluster_id' => $cluster->id]);
    $this->node->storages()->attach($storage);

    $this->clusterStatus = clusteredStatus('prod', [$this->node->name, 'pve-2']);
    $this->caFingerprint = 'FF:00';
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [
            storageRow('local-lvm', $this->node->name, used: 1, total: 10, shared: false, pluginType: 'lvmthin'),
            storageRow('local-lvm', 'pve-2', used: 5, total: 10, shared: false, pluginType: 'lvmthin'),
        ],
    )], 200)]);

    $this->service->handle($this->node);

    // One cluster-wide definition, but each node's `local-lvm` is a physically
    // different disk. A link is a statement about reachability, so a local
    // definition gains one only when an operator registers it on that node.
    expect($storage->refresh()->nodes()->count())->toBe(1)
        ->and($peer->storages()->count())->toBe(0);
});

it('does not link a shared pool onto a node in another cluster', function () {
    // A stranger whose node name collides with a member of this cluster --
    // the strongest version of the trap, and why peers are resolved through
    // the scope rather than by name alone.
    $stranger = Node::factory()->for(Location::factory())->create([
        'name' => 'pve-2',
        'cluster_id' => Cluster::factory()->create([
            'name' => 'somewhere-else',
            'fingerprint' => '99:99',
        ])->id,
    ]);

    $cluster = Cluster::factory()->create([
        'name' => 'prod',
        'fingerprint' => 'FF:00',
        'member_names' => [$this->node->name, 'pve-2'],
    ]);
    $this->node->forceFill(['cluster_id' => $cluster->id])->save();
    $storage = Storage::factory()->create(['name' => 'ceph-vm', 'cluster_id' => $cluster->id]);
    $this->node->storages()->attach($storage);

    $this->clusterStatus = clusteredStatus('prod', [$this->node->name, 'pve-2']);
    $this->caFingerprint = 'FF:00';
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [
            storageRow('ceph-vm', 'pve-2', used: 1, total: 10, shared: true, pluginType: 'rbd'),
        ],
    )], 200)]);

    $this->service->handle($this->node);

    expect($stranger->storages()->count())->toBe(0);
});

it('links nothing beyond itself for a standalone host', function () {
    $storage = Storage::factory()->create(['name' => 'ceph-vm']);
    $this->node->storages()->attach($storage);

    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name)],
        storages: [storageRow('ceph-vm', $this->node->name, used: 1, total: 10, shared: true)],
    )], 200)]);

    $this->service->handle($this->node);

    expect($storage->refresh()->nodes()->count())->toBe(1);
});

it('folds per-node rows into one definition when the cluster is discovered', function () {
    // The shape the v4 upgrade migration leaves behind: every node in its own
    // singleton scope, each with its own `ceph-vm` row linked to itself --
    // which was also v4's answer to registering one pool through three nodes.
    $myScope = Cluster::factory()->standalone()->create();
    $peerScope = Cluster::factory()->standalone()->create();
    $this->node->forceFill(['cluster_id' => $myScope->id])->save();
    $peer = Node::factory()->for(Location::factory())->create([
        'name' => 'pve-2',
        'cluster_id' => $peerScope->id,
    ]);

    $mine = Storage::factory()->create(['name' => 'ceph-vm', 'cluster_id' => $myScope->id]);
    $theirs = Storage::factory()->create(['name' => 'ceph-vm', 'cluster_id' => $peerScope->id]);
    $this->node->storages()->attach($mine);
    $peer->storages()->attach($theirs);

    $this->clusterStatus = clusteredStatus('prod', [$this->node->name, 'pve-2']);
    $this->caFingerprint = 'AB:12';
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);
    $this->service->handle($peer);

    // One definition, both links, and the emptied singletons are gone.
    expect(Storage::query()->where('name', 'ceph-vm')->count())->toBe(1);

    $survivor = Storage::query()->where('name', 'ceph-vm')->first();
    expect($survivor->cluster_id)->toBe($this->node->refresh()->cluster_id)
        ->and($peer->refresh()->cluster_id)->toBe($this->node->cluster_id)
        ->and($survivor->nodes()->pluck('name')->sort()->values()->all())
        ->toBe(collect([$this->node->name, 'pve-2'])->sort()->values()->all())
        ->and(Cluster::query()->whereNull('fingerprint')->count())->toBe(0);
});

it('moves a node that left its cluster into a fresh scope and severs its links', function () {
    // `pvecm delnode` -- or the discouraged separate-without-reinstall, whose
    // stale certificate is exactly why standalone scopes are never keyed by CA.
    $cluster = Cluster::factory()->create([
        'name' => 'prod',
        'fingerprint' => 'FF:AA',
        'member_names' => [$this->node->name, 'pve-2'],
    ]);
    $this->node->forceFill(['cluster_id' => $cluster->id])->save();
    $shared = Storage::factory()->create(['name' => 'ceph-vm', 'cluster_id' => $cluster->id]);
    $this->node->storages()->attach($shared);

    // Default $this->clusterStatus: no cluster row any more.
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);

    $this->node->refresh();
    // The pool belongs to the cluster it left; a node that cannot prove it
    // reaches it must not be offered it.
    expect($this->node->cluster->fingerprint)->toBeNull()
        ->and($this->node->storages()->count())->toBe(0)
        ->and($shared->refresh()->cluster_id)->toBe($cluster->id);
});

it('flags a cluster whose reported members share nothing with the record', function () {
    // Same CA, wholly different members: either every node was renamed at
    // once, or a dirty-separated node re-clustered on the old certificate.
    // Both deserve a human; neither should silently rewrite the record.
    $cluster = Cluster::factory()->create([
        'name' => 'prod',
        'fingerprint' => 'FF:AA',
        'member_names' => ['old-1', 'old-2'],
    ]);
    $this->node->forceFill(['cluster_id' => $cluster->id])->save();

    $this->clusterStatus = clusteredStatus('prod', [$this->node->name, 'new-2']);
    $this->caFingerprint = 'FF:AA';
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload()], 200)]);

    $this->service->handle($this->node);

    $cluster->refresh();
    expect($cluster->flagged_at)->not->toBeNull()
        ->and($cluster->flag_reason)->toContain('old-1')
        ->and($cluster->member_names)->toBe(['old-1', 'old-2']);
});

it('severs a confirmed link the cluster no longer reports', function () {
    $cluster = Cluster::factory()->create([
        'name' => 'prod',
        'fingerprint' => 'FF:00',
        'member_names' => [$this->node->name, 'pve-2'],
    ]);
    $this->node->forceFill(['cluster_id' => $cluster->id])->save();
    $peer = Node::factory()->for(Location::factory())->create([
        'name' => 'pve-2',
        'cluster_id' => $cluster->id,
    ]);
    $storage = Storage::factory()->create(['name' => 'nfs-old', 'cluster_id' => $cluster->id]);
    $this->node->storages()->attach($storage, ['discovered_total' => 10, 'discovered_used' => 1, 'discovered_at' => now()->subMinute()]);
    $peer->storages()->attach($storage, ['discovered_total' => 10, 'discovered_used' => 1, 'discovered_at' => now()->subMinute()]);

    $this->clusterStatus = clusteredStatus('prod', [$this->node->name, 'pve-2']);
    $this->caFingerprint = 'FF:00';
    // The response vouches for both members as online, and reports the pool on
    // the polled node only: pve-2 was removed from the storage's node list.
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [nodeResourceRow($this->node->name), nodeResourceRow('pve-2')],
        storages: [storageRow('nfs-old', $this->node->name, used: 1, total: 10, shared: true)],
    )], 200)]);

    $this->service->handle($this->node);

    expect($storage->refresh()->nodes()->pluck('name')->all())->toBe([$this->node->name]);
});

it('leaves links alone when unconfirmed or when the member is not vouched for', function () {
    $cluster = Cluster::factory()->create([
        'name' => 'prod',
        'fingerprint' => 'FF:00',
        'member_names' => [$this->node->name, 'pve-2', 'pve-3'],
    ]);
    $this->node->forceFill(['cluster_id' => $cluster->id])->save();
    $fresh = Node::factory()->for(Location::factory())->create(['name' => 'pve-2', 'cluster_id' => $cluster->id]);
    $down = Node::factory()->for(Location::factory())->create(['name' => 'pve-3', 'cluster_id' => $cluster->id]);

    $storage = Storage::factory()->create(['name' => 'nfs-a', 'cluster_id' => $cluster->id]);
    // An operator's fresh registration PVE has not acknowledged yet: their
    // claim to make, so it stays visible for them to see and fix.
    $fresh->storages()->attach($storage);
    // Confirmed once, but its node is offline in this response: absent is not
    // detached, and severing would turn an outage into a config change.
    $down->storages()->attach($storage, ['discovered_total' => 10, 'discovered_used' => 1, 'discovered_at' => now()->subMinute()]);

    $this->clusterStatus = clusteredStatus('prod', [$this->node->name, 'pve-2', 'pve-3']);
    $this->caFingerprint = 'FF:00';
    Http::fake(['*/api2/json/cluster/resources' => Http::response(['data' => pollStatusPayload(
        nodes: [
            nodeResourceRow($this->node->name),
            nodeResourceRow('pve-2'),
            nodeResourceRow('pve-3', ['status' => 'offline']),
        ],
        storages: [],
    )], 200)]);

    $this->service->handle($this->node);

    expect($storage->refresh()->nodes()->pluck('name')->sort()->values()->all())
        ->toBe(['pve-2', 'pve-3']);
});
