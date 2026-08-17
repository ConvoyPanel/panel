<?php

use App\Enums\Template\TemplateInstallStatus;
use App\Enums\Template\TemplateSource;
use App\Jobs\Template\InstallTemplateJob;
use App\Models\Anchor;
use App\Models\Node;
use App\Models\Storage;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Models\TemplateInstall;
use App\Models\User;
use App\Services\Templates\CofoundryRegistryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

const REGISTRY_URL = 'https://cofoundry.test/registry.json';

function registryPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'schema_version' => '1',
        'name' => 'Cofoundry Templates',
        'description' => null,
        'generated_at' => '2026-08-04T20:05:25.694Z',
        'groups' => [
            [
                'id' => 'debian',
                'display_name' => 'Debian',
                'description' => null,
                'templates' => [
                    [
                        'name' => 'debian-12-amd64',
                        'display' => 'Debian 12 (Bookworm)',
                        'arch' => 'amd64',
                        'sha256' => str_repeat('a', 64),
                        'size' => 536064079,
                        'url' => 'https://cofoundry.test/debian-12.vma.zst',
                        'built_at' => '2026-08-04T01:37:54Z',
                        'suggested_vmid' => 4001,
                    ],
                ],
            ],
        ],
    ], $overrides);
}

/**
 * Stub the registry and the per-node VMID pre-flight.
 *
 * Both in one call because `Http::fake()` merges stubs first-match-wins: a
 * second `fake()` in the same test cannot override a pattern the first already
 * claimed, so the guests a test wants "already on the node" have to be passed
 * in here.
 *
 * @param  array<int, array<string, mixed>>  $guests  /cluster/resources rows
 */
function fakeRegistry(?array $payload = null, array $guests = []): void
{
    config()->set('convoy.cofoundry.registry_url', REGISTRY_URL);
    Cache::forget(CofoundryRegistryService::CACHE_KEY.':'.sha1(REGISTRY_URL));

    Http::fake([
        REGISTRY_URL => Http::response($payload ?? registryPayload()),
        '*/api2/json/cluster/resources' => Http::response(['data' => $guests]),
        // Cancelling an install reaches the agent directly.
        '*/api/v1/templates/installs/*' => Http::response([], 204),
    ]);
}

/** One `type=qemu` row as /cluster/resources returns it. */
function takenVmidRow(int $vmid): array
{
    return [
        'type' => 'qemu',
        'id' => "qemu/{$vmid}",
        'name' => "vm-{$vmid}",
        'status' => 'running',
        'vmid' => $vmid,
        'node' => 'pve',
        'maxcpu' => 2,
        'cpu' => 0.1,
        'maxmem' => 2048,
        'mem' => 1024,
        'maxdisk' => 100,
        'disk' => 50,
        'uptime' => 10,
    ];
}

/** A node with an agent new enough to install templates, and somewhere to put them. */
function templateNode(string $storage = 'local-zfs'): Node
{
    $node = Node::factory()->create([
        'anchor_id' => Anchor::factory()->installsTemplates(),
    ]);
    $node->storages()->attach(Storage::factory()->create(['name' => $storage, 'stores_kvm' => true]));

    return $node;
}

it('lists the registry with the nodes it can install onto', function () {
    fakeRegistry();
    $node = templateNode();

    $response = $this->actingAs(admin())
        ->getJson('/api/admin/template-registry')
        ->assertOk();

    expect($response->json('data.groups.0.id'))->toBe('debian')
        ->and($response->json('data.groups.0.templates.0.name'))->toBe('debian-12-amd64')
        ->and($response->json('data.groups.0.templates.0.updateAvailable'))->toBeFalse()
        ->and($response->json('data.groups.0.templates.0.installedVmid'))->toBeNull()
        ->and($response->json('data.nodes.0.id'))->toBe($node->id)
        ->and($response->json('data.nodes.0.installable'))->toBeTrue()
        ->and($response->json('data.nodes.0.storages.0'))->toBe('local-zfs');
});

it('says why a node cannot take an install instead of hiding it', function () {
    fakeRegistry();
    // An agent without the capability — an Anchor too old, or one with
    // templates switched off.
    $node = Node::factory()->create(['anchor_id' => Anchor::factory()->enrolled()]);
    $node->storages()->attach(Storage::factory()->create(['stores_kvm' => true]));

    $response = $this->actingAs(admin())
        ->getJson('/api/admin/template-registry')
        ->assertOk();

    expect($response->json('data.nodes.0.installable'))->toBeFalse()
        ->and($response->json('data.nodes.0.reason'))->toContain('does not offer template installs');
});

it('flags a template whose published build differs from the installed one', function () {
    fakeRegistry();
    $group = TemplateGroup::create(['name' => 'Debian', 'source' => 'cofoundry', 'source_id' => 'debian']);
    Template::create([
        'template_group_id' => $group->id,
        'name' => 'Debian 12',
        'vmid' => 4001,
        'source' => TemplateSource::COFOUNDRY->value,
        'source_name' => 'debian-12-amd64',
        'source_sha256' => str_repeat('b', 64),
        'source_built_at' => '2026-01-01T00:00:00Z',
    ]);

    $response = $this->actingAs(admin())
        ->getJson('/api/admin/template-registry')
        ->assertOk();

    expect($response->json('data.groups.0.templates.0.updateAvailable'))->toBeTrue()
        ->and($response->json('data.groups.0.templates.0.installedVmid'))->toBe(4001);
});

it('does not flag an update when the installed build is the published one', function () {
    fakeRegistry();
    $group = TemplateGroup::create(['name' => 'Debian', 'source' => 'cofoundry', 'source_id' => 'debian']);
    Template::create([
        'template_group_id' => $group->id,
        'name' => 'Debian 12',
        'vmid' => 4001,
        'source' => TemplateSource::COFOUNDRY->value,
        'source_name' => 'debian-12-amd64',
        'source_sha256' => str_repeat('a', 64),
        'source_built_at' => '2026-08-04T01:37:54Z',
    ]);

    $this->actingAs(admin())
        ->getJson('/api/admin/template-registry')
        ->assertOk()
        ->assertJsonPath('data.groups.0.templates.0.updateAvailable', false);
});

it('queues one install per node at the suggested vmid', function () {
    fakeRegistry();
    $first = templateNode('local-zfs');
    $second = templateNode('tank');

    $this->actingAs(admin())
        ->postJson('/api/admin/template-registry/import', [
            'templates' => ['debian-12-amd64'],
            'node_ids' => [$first->id, $second->id],
        ])
        ->assertCreated();

    $installs = TemplateInstall::all();

    // The same VMID on every node: a Template row carries one vmid and no node,
    // so a fan-out that assigned per-node ids would produce a template the panel
    // could only offer on one of them.
    expect($installs)->toHaveCount(2)
        ->and($installs->pluck('vmid')->unique()->all())->toBe([4001])
        ->and($installs->pluck('node_id')->sort()->values()->all())
        ->toBe(collect([$first->id, $second->id])->sort()->values()->all())
        ->and($installs->pluck('status')->unique()->all())->toBe([TemplateInstallStatus::QUEUED]);

    Queue::assertPushed(InstallTemplateJob::class, 2);
});

it('defaults each node to its own vm-disk storage', function () {
    fakeRegistry();
    $first = templateNode('local-zfs');
    $second = templateNode('tank');

    $this->actingAs(admin())
        ->postJson('/api/admin/template-registry/import', [
            'templates' => ['debian-12-amd64'],
            'node_ids' => [$first->id, $second->id],
        ])
        ->assertCreated();

    expect(TemplateInstall::where('node_id', $first->id)->value('storage'))->toBe('local-zfs')
        ->and(TemplateInstall::where('node_id', $second->id)->value('storage'))->toBe('tank');
});

it('honours a storage chosen per node', function () {
    fakeRegistry();
    $node = templateNode('local-zfs');
    $node->storages()->attach(Storage::factory()->create(['name' => 'fast', 'stores_kvm' => true]));

    $this->actingAs(admin())
        ->postJson('/api/admin/template-registry/import', [
            'templates' => ['debian-12-amd64'],
            'node_ids' => [$node->id],
            'storage' => [$node->id => 'fast'],
        ])
        ->assertCreated();

    expect(TemplateInstall::first()->storage)->toBe('fast');
});

it('assigns a free vmid when the suggested one is taken', function () {
    config()->set('convoy.cofoundry.vmid_start', 9000);
    fakeRegistry(guests: [takenVmidRow(4001), takenVmidRow(9000)]);
    $node = templateNode();

    $this->actingAs(admin())
        ->postJson('/api/admin/template-registry/import', [
            'templates' => ['debian-12-amd64'],
            'node_ids' => [$node->id],
        ])
        ->assertCreated();

    expect(TemplateInstall::first()->vmid)->toBe(9001);
});

it('reinstalls an update over the vmid the template already uses', function () {
    fakeRegistry();
    $node = templateNode();
    $group = TemplateGroup::create(['name' => 'Debian', 'source' => 'cofoundry', 'source_id' => 'debian']);
    Template::create([
        'template_group_id' => $group->id,
        'name' => 'Debian 12',
        'vmid' => 4321,
        'source' => TemplateSource::COFOUNDRY->value,
        'source_name' => 'debian-12-amd64',
        'source_sha256' => str_repeat('b', 64),
    ]);

    $this->actingAs(admin())
        ->postJson('/api/admin/template-registry/import', [
            'templates' => ['debian-12-amd64'],
            'node_ids' => [$node->id],
        ])
        ->assertCreated();

    $install = TemplateInstall::first();

    // The occupant of 4321 is our own previous install of this template, so the
    // restore has to be allowed to replace it without the admin asking.
    expect($install->vmid)->toBe(4321)
        ->and($install->overwrite)->toBeTrue();
});

it('refuses to import a template the registry no longer offers', function () {
    fakeRegistry();
    $node = templateNode();

    $this->actingAs(admin())
        ->postJson('/api/admin/template-registry/import', [
            'templates' => ['debian-6-amd64'],
            'node_ids' => [$node->id],
        ])
        ->assertConflict();

    expect(TemplateInstall::count())->toBe(0);
});

it('cancels a running install and stands the poller down', function () {
    fakeRegistry();
    $node = templateNode();
    $install = TemplateInstall::create([
        'node_id' => $node->id,
        'source_name' => 'debian-12-amd64',
        'display' => 'Debian 12',
        'url' => 'https://cofoundry.test/debian-12.vma.zst',
        'sha256' => str_repeat('a', 64),
        'vmid' => 4001,
        'storage' => 'local-zfs',
        'status' => TemplateInstallStatus::DOWNLOADING,
        'anchor_job_id' => 'job-1',
    ]);

    $this->actingAs(admin())
        ->deleteJson("/api/admin/template-registry/installs/{$install->uuid}")
        ->assertNoContent();

    expect($install->refresh()->status)->toBe(TemplateInstallStatus::CANCELLED);
});

it('clears a finished install off the list', function () {
    fakeRegistry();
    $node = templateNode();
    $install = TemplateInstall::create([
        'node_id' => $node->id,
        'source_name' => 'debian-12-amd64',
        'display' => 'Debian 12',
        'url' => 'https://cofoundry.test/debian-12.vma.zst',
        'sha256' => str_repeat('a', 64),
        'vmid' => 4001,
        'storage' => 'local-zfs',
        'status' => TemplateInstallStatus::FAILED,
    ]);

    $this->actingAs(admin())
        ->deleteJson("/api/admin/template-registry/installs/{$install->uuid}")
        ->assertNoContent();

    expect(TemplateInstall::count())->toBe(0);
});

it('reports a registry it cannot read rather than serving a broken catalogue', function () {
    config()->set('convoy.cofoundry.registry_url', REGISTRY_URL);
    Cache::forget(CofoundryRegistryService::CACHE_KEY.':'.sha1(REGISTRY_URL));
    Http::fake([REGISTRY_URL => Http::response(registryPayload(['schema_version' => '2']))]);

    $this->actingAs(admin())
        ->getJson('/api/admin/template-registry')
        ->assertBadRequest();
});

it('reports an unreachable registry as unavailable', function () {
    config()->set('convoy.cofoundry.registry_url', REGISTRY_URL);
    Cache::forget(CofoundryRegistryService::CACHE_KEY.':'.sha1(REGISTRY_URL));
    Http::fake([REGISTRY_URL => Http::response('nope', 502)]);

    $this->actingAs(admin())
        ->getJson('/api/admin/template-registry')
        ->assertServiceUnavailable();
});

it('keeps the registry out of reach of non-admins', function () {
    fakeRegistry();

    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->getJson('/api/admin/template-registry')
        ->assertForbidden();
});
