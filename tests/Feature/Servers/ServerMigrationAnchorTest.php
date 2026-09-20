<?php

use App\Actions\Server\MigrateServerAction;
use App\Enums\Network\AddressState;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\MigrationDisposition;
use App\Enums\Server\MigrationTransport;
use App\Enums\Server\ProgressMode;
use App\Enums\Server\ServerLifecycle;
use App\Exceptions\Proxmox\RequestException;
use App\Exceptions\Service\Server\MigrationRefusedException;
use App\Jobs\Server\ExportGuestJob;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Cluster;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\ServerMigrationTransfer;
use App\Models\Storage;
use App\Models\User;
use App\Services\Anchor\AnchorMigrationClient;
use App\Services\Servers\ServerMigrationService;
use App\Support\Anchor\AnchorMigrationProtocol;
use Illuminate\Http\Client\Request;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * The Anchor transport, end to end and in every way it can fail.
 *
 * Almost all of these assert an *order*, not a set. The whole safety argument
 * of docs/migration-anchor-contract.md is that the source guest outlives the
 * destination's verification, and "the destroy happened" is not the property
 * that matters -- "the destroy happened after the verify" is. So the suite
 * reads `Http::recorded()` back as a labelled sequence and asserts on that.
 */
beforeEach(function () {
    // The suite fakes the queue globally, which would make every chain in this
    // file a no-op. Put the real one back and run it inline, so what is under
    // test is the chain as it is actually dispatched rather than a list of
    // jobs a test hand-assembled in the order it expected.
    app()->register(QueueServiceProvider::class, true);
    Queue::clearResolvedInstance('queue');
    config()->set('queue.default', 'sync');

    $location = Location::factory()->create();

    // Two clusters, not one. That is what selects this transport, and a test
    // that put both nodes in one cluster would be exercising `qm migrate`.
    $this->sourceCluster = Cluster::factory()->create();
    $this->destinationCluster = Cluster::factory()->create();

    $this->source = Node::factory()->for($location)->create([
        'name' => 'pve1',
        'fqdn' => 'pve1.example.com',
        'cluster_id' => $this->sourceCluster->id,
        ...anchorColumns('pve1'),
    ]);

    $this->target = Node::factory()->for($location)->create([
        'name' => 'pve2',
        'fqdn' => 'pve2.example.com',
        'cluster_id' => $this->destinationCluster->id,
        ...anchorColumns('pve2'),
    ]);

    // One storage definition per cluster, same name on both. The restore needs
    // somewhere to put the disks and the panel matches by name, exactly as it
    // matches bridges.
    $this->sourceStorage = Storage::factory()->create(['name' => 'local-lvm', 'cluster_id' => $this->sourceCluster->id]);
    $this->destinationStorage = Storage::factory()->create(['name' => 'local-lvm', 'cluster_id' => $this->destinationCluster->id]);
    $this->source->storages()->attach($this->sourceStorage);
    $this->target->storages()->attach($this->destinationStorage);

    $this->pool = AddressBlockGroup::factory()->create(['name' => 'Public /24']);
    $this->block = AddressBlock::factory()->for($this->pool, 'addressBlockGroup')->create([
        'base_ip' => '192.0.2.0',
        'gateway' => '192.0.2.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);

    // A same-named bridge on the same pool on both sides: the addresses
    // follow, so these tests are about the transport and not about IPAM.
    $this->sourceBridge = NetworkInterface::create(['node_id' => $this->source->id, 'name' => 'vmbr0']);
    $this->sourceBridge->addressBlockGroups()->attach($this->pool->id);
    $this->targetBridge = NetworkInterface::create(['node_id' => $this->target->id, 'name' => 'vmbr0']);
    $this->targetBridge->addressBlockGroups()->attach($this->pool->id);

    $this->server = Server::factory()->create([
        'user_id' => User::factory(),
        'node_id' => $this->source->id,
        'network_interface_id' => $this->sourceBridge->id,
        'storage_id' => $this->sourceStorage->id,
        'vmid' => 150,
        'disk' => 34359738368,
    ]);

    $this->held = Address::factory()->for($this->block)->create([
        'ip' => '192.0.2.10',
        'server_id' => $this->server->id,
        'state' => AddressState::Assigned,
    ]);
    $this->server->forceFill(['primary_ipv4_address_id' => $this->held->id])->save();

    $this->action = app(MigrateServerAction::class);
    $this->service = app(ServerMigrationService::class);
});

/** The agent columns an enrolled, online, migration-capable node carries. */
function anchorColumns(string $name): array
{
    return [
        'agent_uuid' => (string) Str::uuid(),
        'agent_secret' => str_repeat('k', 32),
        'agent_public_url' => "https://anchor-{$name}.example.com",
        'agent_enrolled_at' => now(),
        'agent_last_seen_at' => now(),
        'agent_protocol_min' => 1,
        'agent_protocol_max' => 1,
        'agent_capabilities' => [
            AnchorMigrationProtocol::EXPORT_CAPABILITY,
            AnchorMigrationProtocol::INSTALL_CAPABILITY,
        ],
    ];
}

/** One Anchor job record, shaped the way the agent publishes it. */
function anchorJob(string $id, string $status, array $extra = []): array
{
    return array_merge([
        'id' => $id,
        'status' => $status,
        'progress' => $status === 'completed' ? 100 : 40,
        'downloaded' => 0,
        'total' => null,
        'error' => null,
    ], $extra);
}

/** A finished export, with everything the install side needs from it. */
function finishedExport(array $extra = []): array
{
    return anchorJob('job-export', 'completed', array_merge([
        'artifact' => 'artifact-1',
        'sha256' => str_repeat('a', 64),
        'size' => 8_000_000_000,
        'path' => '/var/lib/vz/dump/vzdump-qemu-150.vma.zst',
    ], $extra));
}

/**
 * Every endpoint a whole Anchor migration touches, with the two Anchor jobs
 * reporting whatever the caller wants.
 *
 * First match wins in `Http::fake`, so the overrides go in front. Each test
 * replaces exactly the one response it is about.
 */
function fakeAnchorMigration(array $overrides = [], array $config = []): void
{
    $disk = $config['disk'] ?? 'local-lvm:vm-150-disk-0,size=32G';

    // `+` rather than array_merge: the overrides have to keep both their value
    // and their position, because Http::fake matches in insertion order and a
    // pattern behind the `*` catch-all is a pattern that never fires.
    Http::fake($overrides + [
        // Proxmox, source side.
        '*/qemu/*/migrate*' => Http::response(migratePreconditions(), 200),
        'https://pve1.example.com*/qemu/150/config*' => Http::response(['data' => ['scsi0' => $disk]], 200),
        // Proxmox, destination side. `cluster/nextid?vmid=N` is PVE's "is this
        // one free" -- an error means taken. 150 is taken on pve2, which is
        // the case this transport exists for, so the panel allocates 900.
        'https://pve2.example.com*/cluster/nextid*' => function (Request $request) {
            return str_contains($request->url(), 'vmid=150')
                ? Http::response(['errors' => ['vmid' => 'VM 150 already exists']], 400)
                : Http::response(['data' => 900], 200);
        },
        'https://pve2.example.com*/qemu/900/config*' => Http::response(['data' => ['scsi0' => $disk]], 200),
        // The guest's power state, which the stop and start steps poll.
        '*/status/current*' => Http::response(['data' => [
            'status' => 'stopped', 'uptime' => 0, 'cpu' => 0, 'maxmem' => 1024, 'mem' => 0,
        ]], 200),

        // Anchor, source side.
        '*/api/v1/templates/exports' => Http::response(anchorJob('job-export', 'pending'), 202),
        '*/api/v1/templates/jobs/*' => Http::response(finishedExport(), 200),
        '*/api/v1/templates/artifacts/*' => Http::response(['status' => 'ok'], 200),

        // Anchor, destination side.
        '*/api/v1/templates/installs' => Http::response(anchorJob('job-install', 'pending'), 202),
        '*/api/v1/templates/installs/*' => Http::response(anchorJob('job-install', 'completed'), 200),

        // The source guest is gone once the destroy has run.
        '*/cluster/resources*' => Http::response(['data' => []], 200),
        '*' => Http::response(['data' => 'UPID:task'], 200),
    ]);
}

/**
 * Every request made, as a list of labels in the order they went out.
 *
 * @return array<int, string>
 */
function anchorCallSequence(): array
{
    return collect(Http::recorded())
        ->map(function (array $pair) {
            /** @var Request $request */
            $request = $pair[0];
            $url = $request->url();
            $method = $request->method();

            return match (true) {
                str_contains($url, '/templates/exports') => 'export',
                str_contains($url, '/templates/jobs') && $method === 'GET' => 'export-status',
                str_contains($url, '/templates/jobs') && $method === 'DELETE' => 'export-cancel',
                str_contains($url, '/templates/artifacts') => 'discard',
                str_contains($url, '/templates/installs') && $method === 'POST' => 'install',
                str_contains($url, '/templates/installs') && $method === 'GET' => 'install-status',
                str_contains($url, '/templates/installs') && $method === 'DELETE' => 'install-cancel',
                str_contains($url, 'pve2.example.com') && str_contains($url, '/config') => 'verify-destination',
                str_contains($url, 'pve1.example.com') && str_contains($url, '/config') => 'read-source-config',
                str_contains($url, 'pve2.example.com') && $method === 'DELETE' => 'destroy-destination',
                str_contains($url, 'pve1.example.com') && $method === 'DELETE' => 'destroy-source',
                str_contains($url, '/status/start') => 'start',
                str_contains($url, '/status/stop') => 'stop',
                default => 'other',
            };
        })
        ->reject(fn (string $label) => $label === 'other')
        ->values()
        ->all();
}

it('picks the Anchor transport for a node outside the cluster, and says what it costs', function () {
    fakeAnchorMigration();

    $plan = $this->service->plan($this->server);

    expect($plan->candidates)->toHaveCount(1);

    $candidate = $plan->candidates[0];

    expect($candidate->nodeId)->toBe($this->target->id)
        ->and($candidate->transport)->toBe(MigrationTransport::Anchor)
        ->and($candidate->disposition)->toBe(MigrationDisposition::Preserve)
        // The cost, attached to the thing it is a cost of: this transport
        // cannot move a running guest, and the operator is told the size of
        // what has to cross the wire before they commit to it.
        ->and($candidate->canMigrateOnline)->toBeFalse()
        ->and($candidate->estimatedTransferBytes)->toBe(34359738368);
});

it('keeps using qm migrate for a node inside the cluster', function () {
    fakeAnchorMigration();
    $this->target->forceFill(['cluster_id' => $this->sourceCluster->id])->save();

    $plan = $this->service->plan($this->server->fresh());

    expect($plan->candidates)->toHaveCount(1)
        ->and($plan->candidates[0]->transport)->toBe(MigrationTransport::Cluster)
        ->and($plan->candidates[0]->estimatedTransferBytes)->toBeNull();
});

it('runs export, install, verify and only then destroys the source', function () {
    fakeAnchorMigration();

    $deployment = $this->action->execute($this->server, $this->target, false);

    expect($deployment->steps()->orderBy('sequence')->pluck('name')->all())->toBe([
        'export-guest',
        'install-guest',
        'verify-guest',
        'discard-artifact',
        'destroy-source',
        'rebind-network',
    ]);

    // The ordering, not the membership. Everything else in this file exists to
    // defend the position of `destroy-source` in this list.
    expect(anchorCallSequence())->toBe([
        'export',
        'export-status',
        'install',
        'install-status',
        'verify-destination',
        'read-source-config',
        'discard',
        'destroy-source',
    ]);

    expect($this->server->fresh())
        ->node_id->toBe($this->target->id)
        ->vmid->toBe(900)
        ->storage_id->toBe($this->destinationStorage->id)
        ->network_interface_id->toBe($this->targetBridge->id)
        ->lifecycle->toBe(ServerLifecycle::READY);

    // A preserving migration touches no address at all.
    expect($this->held->fresh())->server_id->toBe($this->server->id)->state->toBe(AddressState::Assigned);
});

it('stops the guest before exporting it and starts it again on the destination', function () {
    fakeAnchorMigration(['*/qemu/*/migrate*' => Http::response(migratePreconditions(['running' => true]), 200)]);

    $deployment = $this->action->execute($this->server, $this->target, false);

    expect($deployment->steps()->orderBy('sequence')->pluck('name')->all())->toBe([
        'stop-vm',
        'export-guest',
        'install-guest',
        'verify-guest',
        'discard-artifact',
        'destroy-source',
        'rebind-network',
        'start-vm',
    ]);

    $sequence = anchorCallSequence();

    // The archive is taken from a guest that is not writing to its disks, and
    // the downtime ends when the destination guest starts. Both ends of that
    // sentence are assertions.
    expect(array_search('stop', $sequence, true))->toBeLessThan(array_search('export', $sequence, true))
        ->and(array_search('start', $sequence, true))->toBeGreaterThan(array_search('destroy-source', $sequence, true));
});

it('carries the artifact hash and the destination VMID through to the install', function () {
    fakeAnchorMigration();

    $this->action->execute($this->server, $this->target, false);

    $install = installRequest();

    expect($install)->not->toBeNull();

    // The work order travels inside the token, so this is what the agent will
    // actually act on. A hash in the body and not in the token would be a hash
    // an attacker could swap.
    $claims = tokenClaims($install->header('Authorization')[0] ?? '');

    expect($claims['template']['action'])->toBe('install')
        ->and($claims['template']['sha256'])->toBe(str_repeat('a', 64))
        ->and($claims['template']['vmid'])->toBe(900)
        ->and($claims['template']['storage'])->toBe('local-lvm')
        ->and($claims['template']['url'])->toContain('/artifacts/artifact-1')
        // One artifact, one audience: the download token names the artifact
        // and is minted for the source node, not for the panel or the fleet.
        ->and($claims['template']['url'])->toContain('token=');
});

it('mints a download token that names one artifact and one node', function () {
    fakeAnchorMigration();

    $this->action->execute($this->server, $this->target, false);

    $install = installRequest();

    $url = tokenClaims($install->header('Authorization')[0] ?? '')['template']['url'];
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    $fetch = tokenClaims('Bearer '.$query['token']);

    expect($fetch['template'])->toBe(['action' => 'fetch', 'artifact' => 'artifact-1'])
        ->and($fetch['aud'])->toBe($this->source->agent_uuid);
});

/** The POST that started the install on the destination. */
function installRequest(): ?Request
{
    $pair = collect(Http::recorded())
        ->first(fn (array $pair) => str_contains($pair[0]->url(), '/templates/installs') && $pair[0]->method() === 'POST');

    return $pair === null ? null : $pair[0];
}

/** Decode a bearer token's claims without verifying it; the panel signed it. */
function tokenClaims(string $header): array
{
    $token = str_replace('Bearer ', '', $header);
    $parts = explode('.', $token);

    return json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) ?? [];
}

/**
 * Every failure before the destroy has the same three consequences, so they
 * are asserted the same way rather than written out five times.
 */
dataset('anchor failures', [
    'the export fails' => [
        ['*/api/v1/templates/jobs/*' => fn () => Http::response(anchorJob('job-export', 'failed', ['error' => 'vzdump exited 1']), 200)],
        'vzdump exited 1',
    ],
    'the export names no artifact' => [
        ['*/api/v1/templates/jobs/*' => fn () => Http::response(anchorJob('job-export', 'completed'), 200)],
        'named no artifact',
    ],
    'the destination rejects the hash' => [
        ['*/api/v1/templates/installs/*' => fn () => Http::response(anchorJob('job-install', 'failed', ['error' => 'checksum mismatch for convoy-150']), 200)],
        'checksum mismatch',
    ],
    'the install fails' => [
        ['*/api/v1/templates/installs/*' => fn () => Http::response(anchorJob('job-install', 'failed', ['error' => 'qmrestore exited 2']), 200)],
        'qmrestore exited 2',
    ],
    'the destination guest is not there afterwards' => [
        ['https://pve2.example.com*/qemu/900/config*' => fn () => Http::response(['data' => []], 200)],
        'no guest at VMID 900',
    ],
    'the destination guest came back the wrong size' => [
        ['https://pve2.example.com*/qemu/900/config*' => fn () => Http::response(['data' => ['scsi0' => 'local-lvm:vm-900-disk-0,size=8G']], 200)],
        'wrong size',
    ],
    'the artifact cannot be discarded' => [
        ['*/api/v1/templates/artifacts/*' => fn () => Http::response(['error' => 'artifact is locked'], 409)],
        'artifact is locked',
    ],
    'the export is cancelled mid-transfer' => [
        ['*/api/v1/templates/jobs/*' => fn () => Http::response(anchorJob('job-export', 'cancelled'), 200)],
        'cancelled',
    ],
    'the install is cancelled mid-transfer' => [
        ['*/api/v1/templates/installs/*' => fn () => Http::response(anchorJob('job-install', 'cancelled'), 200)],
        'cancelled',
    ],
]);

it('leaves the source guest alone and frees the destination when', function (array $override, string $reason) {
    fakeAnchorMigration(array_map(fn (callable $factory) => $factory(), $override));

    // A reallocating migration, so there is a reservation to leak. The
    // destination has no bridge of the source's name, but it has one that can
    // supply replacements, so the server would have been given a new address
    // if this had worked.
    $this->targetBridge->addressBlockGroups()->detach($this->pool->id);
    $this->targetBridge->forceFill(['name' => 'vmbr1'])->save();
    $otherPool = AddressBlockGroup::factory()->create(['name' => 'Rack B /24']);
    $otherBlock = AddressBlock::factory()->for($otherPool, 'addressBlockGroup')->create([
        'base_ip' => '198.51.100.0',
        'gateway' => '198.51.100.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 32,
    ]);
    $free = Address::factory()->for($otherBlock)->create(['ip' => '198.51.100.20', 'server_id' => null]);
    $this->targetBridge->addressBlockGroups()->attach($otherPool->id);

    try {
        $this->action->execute($this->server, $this->target, true);
        $this->fail('The migration should not have finished.');
    } catch (Throwable $exception) {
        expect($exception->getMessage())->toContain($reason);
    }

    $sequence = anchorCallSequence();

    // The one assertion this whole dataset exists for.
    expect($sequence)->not->toContain('destroy-source');

    expect($this->server->fresh())
        // The row still describes where the guest is, because the guest never
        // went anywhere.
        ->node_id->toBe($this->source->id)
        ->vmid->toBe(150)
        ->lifecycle->toBe(ServerLifecycle::MIGRATION_FAILED);

    expect($this->held->fresh())
        ->server_id->toBe($this->server->id)
        ->state->toBe(AddressState::Assigned);

    // The reservation is not allowed to outlive the attempt.
    expect($free->fresh())
        ->server_id->toBeNull()
        ->state->toBe(AddressState::Available)
        ->state_reason->toBeNull();

    // `source_destroyed_at`, not `verified_at`: the discard case gets past
    // verification and is still rolled back, because the source is still
    // there. That is the line the rollback actually draws.
    expect(ServerMigrationTransfer::firstOrFail())
        ->source_destroyed_at->toBeNull()
        ->rolled_back_at->not->toBeNull();
})->with('anchor failures');

it('restarts a guest it stopped, and leaves a stopped one stopped', function () {
    fakeAnchorMigration([
        '*/qemu/*/migrate*' => Http::response(migratePreconditions(['running' => true]), 200),
        '*/api/v1/templates/installs/*' => Http::response(anchorJob('job-install', 'failed', ['error' => 'qmrestore exited 2']), 200),
    ]);

    try {
        $this->action->execute($this->server, $this->target, false);
    } catch (Throwable) {
        // The failure is the point; what it left behind is the assertion.
    }

    $sequence = anchorCallSequence();

    expect($sequence)->toContain('start')
        ->and($sequence)->not->toContain('destroy-source')
        // Whatever the destination started is cleaned up, or the retry finds
        // the VMID taken and there are two guests where there should be one.
        ->and($sequence)->toContain('destroy-destination');
});

it('does not leave a duplicate guest behind when a failed attempt is retried', function () {
    // One fake for both attempts, because Http::fake merges rather than
    // replaces: the first install fails, the second one works.
    $attempt = 0;
    fakeAnchorMigration(['*/api/v1/templates/installs/*' => function () use (&$attempt) {
        $attempt++;

        return $attempt === 1
            ? Http::response(anchorJob('job-install', 'failed', ['error' => 'qmrestore exited 2']), 200)
            : Http::response(anchorJob('job-install', 'completed'), 200);
    }]);

    try {
        $this->action->execute($this->server, $this->target, false);
    } catch (Throwable) {
    }

    $this->server->forceFill(['lifecycle' => ServerLifecycle::READY])->save();

    // The retry succeeds. The destination VMID is free because the rollback
    // made it free, so the guest lands exactly once.
    $this->action->execute($this->server->fresh(), $this->target, false);

    expect(anchorCallSequence())->toContain('destroy-source');

    expect($this->server->fresh())
        ->node_id->toBe($this->target->id)
        ->vmid->toBe(900);

    // Two attempts, one guest: the first transfer was rolled back and the
    // second is the one that landed.
    expect(ServerMigrationTransfer::count())->toBe(2)
        ->and(ServerMigrationTransfer::whereNotNull('verified_at')->count())->toBe(1);
});

it('refuses before touching anything when either node has no Anchor', function (string $which) {
    fakeAnchorMigration();
    $this->{$which}->forceFill(['agent_uuid' => null, 'agent_enrolled_at' => null])->save();

    $candidate = $this->service->plan($this->server->fresh())->candidates[0];

    expect($candidate->disposition)->toBe(MigrationDisposition::Blocked)
        ->and($candidate->blockedReason)->toContain('Anchor is not installed');

    expect(fn () => $this->action->execute($this->server->fresh(), $this->target->fresh(), false))
        ->toThrow(MigrationRefusedException::class);

    expect($this->server->fresh()->lifecycle)->toBe(ServerLifecycle::READY)
        ->and(anchorCallSequence())->toBe([]);
})->with(['source', 'target']);

it('refuses when an enrolled Anchor cannot do migrations yet', function () {
    fakeAnchorMigration();
    // Enrolled, online, and simply older than this feature. Accepting the
    // install anyway is what turns the migrated guest into a template.
    $this->source->forceFill(['agent_capabilities' => ['console.qemu.vnc']])->save();

    $candidate = $this->service->plan($this->server->fresh())->candidates[0];

    expect($candidate->disposition)->toBe(MigrationDisposition::Blocked)
        ->and($candidate->blockedReason)->toContain('too old to export');
});

it('refuses when the destination has no storage of that name', function () {
    fakeAnchorMigration();
    $this->destinationStorage->forceFill(['name' => 'ceph-fast'])->save();

    $candidate = $this->service->plan($this->server->fresh())->candidates[0];

    expect($candidate->disposition)->toBe(MigrationDisposition::Blocked)
        ->and($candidate->blockedReason)->toContain('no storage named "local-lvm"');

    expect(fn () => $this->action->execute($this->server->fresh(), $this->target->fresh(), false))
        ->toThrow(MigrationRefusedException::class);
});

it('refuses when the disposition is blocked', function () {
    fakeAnchorMigration();
    // A same-named bridge on the destination that is not attached to the pool.
    // Convoy refuses rather than guessing that two `vmbr0`s are one network.
    $this->targetBridge->addressBlockGroups()->detach($this->pool->id);

    $candidate = $this->service->plan($this->server->fresh())->candidates[0];

    expect($candidate->disposition)->toBe(MigrationDisposition::Blocked);

    expect(fn () => $this->action->execute($this->server->fresh(), $this->target->fresh(), false))
        ->toThrow(MigrationRefusedException::class);

    expect(anchorCallSequence())->toBe([]);
});

it('refuses an LXC guest', function () {
    // Every `servers` row is a QEMU guest by construction, so the refusal is
    // Proxmox's: the migrate preflight lives under `/qemu/`, and a container's
    // VMID is not there.
    fakeAnchorMigration(['*/qemu/*/migrate*' => Http::response(['data' => null, 'errors' => ['vmid' => 'no such vm']], 500)]);

    expect(fn () => $this->service->plan($this->server))->toThrow(RequestException::class);

    expect(anchorCallSequence())->not->toContain('export');
});

it('keeps the guest VMID when the destination has it free', function () {
    fakeAnchorMigration([
        // `cluster/nextid?vmid=150` answering without an error is PVE saying
        // that VMID is free, so the guest keeps the number it had.
        'https://pve2.example.com*/cluster/nextid*' => Http::response(['data' => 150], 200),
        'https://pve2.example.com*/qemu/150/config*' => Http::response(['data' => ['scsi0' => 'local-lvm:vm-150-disk-0,size=32G']], 200),
    ]);

    $this->action->execute($this->server, $this->target, false);

    expect($this->server->fresh()->vmid)->toBe(150)
        ->and(ServerMigrationTransfer::firstOrFail()->destination_vmid)->toBe(150);
});

it('keeps polling through a phase it has never seen', function () {
    // `dumping` is the agent's long phase and was added after this panel's
    // enum. An unknown status has to read as "still working": treating it as a
    // failure would abandon an export that is going perfectly well.
    //
    // Driven a poll at a time rather than through the chain, because the sync
    // queue this file runs on turns `release()` into a no-op -- the one place
    // where running the real chain would prove less than driving the job.
    $polls = 0;
    fakeAnchorMigration(['*/api/v1/templates/jobs/*' => function () use (&$polls) {
        $polls++;

        return match ($polls) {
            1 => Http::response(anchorJob('job-export', 'dumping'), 200),
            2 => Http::response(anchorJob('job-export', 'a-phase-from-a-newer-agent'), 200),
            default => Http::response(finishedExport(), 200),
        };
    }]);

    $deployment = $this->server->deployments()->create([
        'type' => DeploymentType::MIGRATE,
        'status' => DeploymentStatus::PENDING,
        'start_on_completion' => false,
        'requested_at' => now(),
    ]);

    $transfer = ServerMigrationTransfer::create([
        'deployment_id' => $deployment->id,
        'server_id' => $this->server->id,
        'source_node_id' => $this->source->id,
        'destination_node_id' => $this->target->id,
        'source_vmid' => 150,
        'destination_vmid' => 900,
        'destination_storage' => 'local-lvm',
    ]);

    $step = $deployment->addSteps([[
        'name' => 'export-guest',
        'status' => DeploymentStatus::PENDING,
        'progress_mode' => ProgressMode::DETERMINATE,
    ]])[0];

    $job = new ExportGuestJob($step, $transfer->id);
    $client = app(AnchorMigrationClient::class);

    $job->handle($client);
    expect($step->fresh()->status)->toBe(DeploymentStatus::RUNNING);

    $job->handle($client);
    expect($step->fresh()->status)->toBe(DeploymentStatus::RUNNING);

    $job->handle($client);

    expect($step->fresh()->status)->toBe(DeploymentStatus::COMPLETED)
        ->and($polls)->toBe(3)
        // The export was issued once, however many times it was polled.
        ->and(collect(Http::recorded())->filter(
            fn (array $pair) => str_contains($pair[0]->url(), '/templates/exports'),
        ))->toHaveCount(1)
        ->and($transfer->fresh())
        ->artifact->toBe('artifact-1')
        ->sha256->toBe(str_repeat('a', 64));
});

it('asks the node that actually holds the guest to export it', function () {
    // The agent refuses a VMID that is not in its own `qemu-server` directory,
    // so an export addressed to the destination fails rather than quietly
    // dumping the wrong guest.
    fakeAnchorMigration();

    $this->action->execute($this->server, $this->target, false);

    $export = collect(Http::recorded())
        ->first(fn (array $pair) => str_contains($pair[0]->url(), '/templates/exports'));

    $claims = tokenClaims($export[0]->header('Authorization')[0] ?? '');

    expect($export[0]->url())->toStartWith($this->source->agent_public_url)
        ->and($claims['aud'])->toBe($this->source->agent_uuid)
        // `stop` and `zstd` are the agent's own defaults, and the panel sends
        // them anyway: the guest being down for the whole transfer is what
        // makes this correct, and it should be legible on the wire.
        ->and($claims['template'])
        ->toBe(['action' => 'export', 'vmid' => 150, 'mode' => 'stop', 'compress' => 'zstd']);
});

it('exports again when the artifact expired out from under the transfer', function () {
    // An artifact has a TTL on the node and does not survive an Anchor
    // restart. The source guest is still sitting there untouched, so the
    // answer is another dump rather than a failed migration.
    $installs = 0;
    fakeAnchorMigration(['*/api/v1/templates/installs/*' => function () use (&$installs) {
        $installs++;

        return $installs === 1
            ? Http::response(anchorJob('job-install', 'failed', ['error' => 'download failed: 404 Not Found']), 200)
            : Http::response(anchorJob('job-install', 'completed'), 200);
    }]);

    $this->action->execute($this->server, $this->target, false);

    expect(array_slice(anchorCallSequence(), 0, 7))->toBe([
        'export',
        'export-status',
        'install',
        'install-status',
        'export',
        'export-status',
        'install',
    ]);

    expect($this->server->fresh())->node_id->toBe($this->target->id)->vmid->toBe(900);

    expect(ServerMigrationTransfer::firstOrFail())
        ->export_attempts->toBe(2)
        ->source_destroyed_at->not->toBeNull();
});

it('stops re-exporting once the node has lost the artifact twice', function () {
    fakeAnchorMigration([
        '*/api/v1/templates/installs/*' => Http::response(
            anchorJob('job-install', 'failed', ['error' => 'download failed: 404 Not Found']),
            200,
        ),
    ]);

    try {
        $this->action->execute($this->server, $this->target, false);
        $this->fail('The migration should not have finished.');
    } catch (Throwable $exception) {
        expect($exception->getMessage())->toContain('re-exporting it did not help');
    }

    expect(ServerMigrationTransfer::firstOrFail())
        ->export_attempts->toBe(ServerMigrationTransfer::MAX_EXPORT_ATTEMPTS)
        ->source_destroyed_at->toBeNull()
        ->rolled_back_at->not->toBeNull();

    expect(anchorCallSequence())->not->toContain('destroy-source');
});

/**
 * The agent's install pipeline exists to import images, and Convoy only offers a
 * guest that reports `template: 1`, so a restore ends with `qm template` unless
 * it is told not to. A migration restores the tenant's own machine: letting that
 * default stand hands them back a guest that cannot be started, after a
 * migration that reported success.
 *
 * The agent defaults the flag to true so every existing import caller stays
 * correct, which is exactly why the panel has to send it explicitly.
 */
it('tells the destination not to turn the migrated guest into a template', function () {
    fakeAnchorMigration();

    $this->action->execute($this->server, $this->target, false);

    // The work order travels inside the bearer token's `template` claim, not
    // the body, so this has to read the claim the agent will actually decode.
    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/templates/installs') || $request->method() !== 'POST') {
            return false;
        }

        $token = Str::after($request->header('Authorization')[0] ?? '', 'Bearer ');
        $claims = json_decode(
            base64_decode(strtr(explode('.', $token)[1] ?? '', '-_', '+/')) ?: '[]',
            true,
        );

        expect($claims['template'])->toHaveKey('finalize_as_template')
            ->and($claims['template']['finalize_as_template'])->toBeFalse();

        return true;
    });
});
