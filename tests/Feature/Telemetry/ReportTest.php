<?php

use Convoy\Models\Node;
use Convoy\Models\User;
use Convoy\Services\Telemetry\PageViewCounter;
use Convoy\Services\Telemetry\TelemetryReportService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\TelemetryContract;

/** Every string value a report holds. Map keys are checked by TelemetryContract. */
function telemetryReportStrings(array $report): array
{
    $strings = [];

    array_walk_recursive($report, function ($value) use (&$strings) {
        if (is_string($value)) {
            $strings[] = $value;
        }
    });

    return $strings;
}

$installation = '6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51';

beforeEach(function () {
    Cache::flush();

    Http::fake([
        '*/api2/json/nodes/*/status' => Http::response(['data' => [
            'pveversion' => 'pve-manager/8.4.1/2a5fa54a8503f96d',
            'cpuinfo' => ['cpus' => 64, 'model' => 'AMD EPYC 7763 64-Core Processor'],
            'memory' => ['total' => 270582939648],
        ]]),
    ]);
});

it('builds a report the console accepts', function () use ($installation) {
    createServerModel();

    $report = app(TelemetryReportService::class)->build($installation);

    expect(TelemetryContract::errors(json_decode(json_encode($report), true)))->toBe([])
        ->and(array_keys($report))->toBe(['schema', 'installation', 'panel', 'users', 'nodes', 'resources', 'features', 'views'])
        ->and($report['installation'])->toBe($installation)
        ->and($report['panel']['version'])->toBe(config('app.version'))
        ->and($report['panel']['php'])->toMatch('/^\d+\.\d+\.\d+$/');
});

it('carries nothing that names a person or the panel', function () use ($installation) {
    config(['app.url' => 'https://panel.acme-hosting.test']);

    [$user, $location, $node, $server] = createServerModel();
    $user->update(['name' => 'Jane Doe', 'email' => 'jane@acme-hosting.test']);
    $location->update(['short_code' => 'acme-fra', 'description' => 'Acme Frankfurt']);
    $node->update(['name' => 'Acme Node One', 'fqdn' => 'pve1.acme-hosting.test', 'cluster' => 'acme-pve1']);
    $server->update(['name' => 'Acme Web', 'hostname' => 'web.acme-hosting.test']);

    $report = app(TelemetryReportService::class)->build($installation);
    $json = json_encode($report);

    foreach (['acme', 'Acme', 'Jane', config('app.key'), 'EPYC'] as $text) {
        expect($json)->not->toContain($text);
    }

    // The only strings are the id, version numbers, the database driver and map keys.
    foreach (telemetryReportStrings($report) as $string) {
        expect($string)->toMatch('/^(?:'.preg_quote($installation).'|canary|\d+(?:\.\d+){1,3}|[a-z0-9]+)$/');
    }
});

it('counts the accounts active in the last day, week and month', function () use ($installation) {
    $before = app(TelemetryReportService::class)->build($installation)['users'];

    User::factory()->create(['root_admin' => true])->forceFill(['last_active_at' => now()->subHour()])->saveQuietly();
    foreach ([now()->subHours(2), now()->subDays(3), now()->subDays(20), now()->subDays(40), null] as $lastActive) {
        User::factory()->create()->forceFill(['last_active_at' => $lastActive])->saveQuietly();
    }

    $after = app(TelemetryReportService::class)->build($installation)['users'];

    expect($after['total'] - $before['total'])->toBe(6)
        ->and($after['admins'] - $before['admins'])->toBe(1)
        ->and($after['active']['day'] - $before['active']['day'])->toBe(2)
        ->and($after['active']['week'] - $before['active']['week'])->toBe(3)
        ->and($after['active']['month'] - $before['active']['month'])->toBe(4)
        ->and($after['active_admins']['day'] - $before['active_admins']['day'])->toBe(1)
        ->and($after['active_admins']['month'] - $before['active_admins']['month'])->toBe(1);
});

it('reports each node with its Proxmox version, CPUs, memory and servers', function () use ($installation) {
    [, , $node] = createServerModel();

    $nodes = app(TelemetryReportService::class)->build($installation)['nodes'];

    // Nodes are listed by id, so the one just made is last.
    expect(end($nodes))->toBe([
        'proxmox' => '8.4.1',
        'agent' => null,
        'cpus' => 64,
        'memory_gib' => 64,
        'servers' => 1,
    ]);
});

it('reports a node that does not answer without the details only Proxmox knows', function () use ($installation) {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Connection timed out'));

    createServerModel();

    $report = app(TelemetryReportService::class)->build($installation);

    expect(end($report['nodes']))->toBe([
        'proxmox' => null,
        'agent' => null,
        'cpus' => 0,
        'memory_gib' => 64,
        'servers' => 1,
    ])->and(TelemetryContract::errors(json_decode(json_encode($report), true)))->toBe([]);
});

it('counts resources and switches without naming them', function () use ($installation) {
    $before = app(TelemetryReportService::class)->build($installation);

    [, , $node] = createServerModel();
    $node->update(['verify_tls' => false]);

    $after = app(TelemetryReportService::class)->build($installation);

    expect($after['resources']['locations'] - $before['resources']['locations'])->toBe(1)
        ->and($after['resources']['nodes.without_tls_verification'] - $before['resources']['nodes.without_tls_verification'])->toBe(1)
        ->and($after['features']['coterm'])->toBeBool();
});

it('stays inside the console\'s limits on a panel past them', function () use ($installation) {
    [, , $node] = createServerModel();
    $node->forceFill(['memory' => 1_500_000 * 1024 * 1024 * 1024])->saveQuietly();

    Http::swap(new Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake(['*/api2/json/nodes/*/status' => Http::response(['data' => [
        'pveversion' => 'pve-manager/8.4.1/2a5fa54a8503f96d',
        'cpuinfo' => ['cpus' => 50000],
    ]])]);

    $views = app(PageViewCounter::class);
    $views->record('/');
    Cache::increment('telemetry:views:client.servers.index', 20_000_000);

    $report = app(TelemetryReportService::class)->build($installation);

    expect(end($report['nodes']))->toMatchArray(['cpus' => 10_000, 'memory_gib' => 1_000_000])
        ->and((array) $report['views'])->toBe(['client.servers.index' => 10_000_000])
        ->and(TelemetryContract::errors(json_decode(json_encode($report), true)))->toBe([]);
});

it('reports at most the console\'s 500 nodes', function () use ($installation) {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: Connection timed out'));

    [, $location] = createServerModel();
    Node::factory()->for($location)->count(500)->create();

    $report = app(TelemetryReportService::class)->build($installation);

    expect($report['nodes'])->toHaveCount(500);
});

it('documents every resource and feature key in docs/telemetry.md', function () use ($installation) {
    $docs = file_get_contents(base_path('docs/telemetry.md'));
    $report = app(TelemetryReportService::class)->build($installation);

    foreach ([...array_keys($report['resources']), ...array_keys($report['features'])] as $key) {
        expect($docs)->toContain("| `{$key}` |");
    }

    foreach (PageViewCounter::PAGES as $name) {
        expect($docs)->toContain("`{$name}`");
    }
});
