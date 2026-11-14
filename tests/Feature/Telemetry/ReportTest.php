<?php

use Carbon\CarbonImmutable;
use Convoy\Enums\Api\ApiKeyType;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Services\Telemetry\TelemetryReportBuilder;
use Convoy\Services\Telemetry\ViewCounter;
use Illuminate\Support\Facades\Cache;
use Tests\Support\TelemetryContract;

$build = fn () => app(TelemetryReportBuilder::class)->build(CarbonImmutable::now())->payload;

beforeEach(function () {
    Cache::flush();
    config(['app.version' => '4.7.0']);
});

it('builds a report the console accepts from a populated panel', function () use ($build) {
    $node = Node::factory()->for(Location::factory())->create([
        'name' => 'Frankfurt rack 4',
        'fqdn' => 'pve1.secret-host.example.com',
        'memory' => 64 * 1024 ** 3,
    ]);
    $user = User::factory()->create(['email' => 'jane@customer.example', 'name' => 'Jane Customer']);
    Server::factory()->count(2)->for($node)->for($user)->create(['hostname' => 'customer-vm.example.net']);
    app(ViewCounter::class)->record('admin.nodes.show');

    $report = $build();

    expect(TelemetryContract::errors($report))->toBe([])
        ->and($report['nodes'])->toBe([[
            'proxmox' => null,
            'agent' => null,
            'cpus' => 0,
            'memory_gib' => 64,
            'servers' => 2,
        ]])
        ->and($report['views'])->toBe(['admin.nodes.show' => 1])
        ->and($report)->not->toHaveKey('activity');
});

it('carries no names, addresses, hostnames or secrets', function () use ($build) {
    config([
        'app.url' => 'https://panel.secret-host.example.com',
        'app.name' => 'Acme Hosting Panel',
    ]);

    $location = Location::factory()->create(['short_code' => 'acme-fra', 'description' => 'Acme Frankfurt']);
    $node = Node::factory()->for($location)->create([
        'name' => 'Frankfurt rack 4',
        'fqdn' => 'pve1.secret-host.example.com',
        'token_id' => 'root@pam!acme',
    ]);
    $user = User::factory()->create(['email' => 'jane@customer.example', 'name' => 'Jane Customer', 'root_admin' => true]);
    Server::factory()->for($node)->for($user)->create(['hostname' => 'customer-vm.example.net', 'name' => 'Customer VM']);
    $pool = AddressPool::factory()->create(['name' => 'Acme public']);
    Address::factory()->create(['address_pool_id' => $pool->id, 'type' => 'ipv4', 'address' => '198.51.100.23', 'gateway' => '198.51.100.1']);

    $report = $build();
    $json = json_encode($report);

    foreach ([
        'secret-host', 'example', 'Acme', 'acme', 'Frankfurt', 'Jane', 'Customer', '198.51.100.23',
        'root@pam', config('app.key'),
    ] as $needle) {
        expect($json)->not->toContain($needle);
    }

    // Every string in the report is a version, a slug, a UUID or a database name.
    array_walk_recursive($report, function (mixed $value) {
        if (is_string($value)) {
            expect($value)->toMatch('/^(?:[0-9a-f-]{36}|v?\d+(?:\.\d+){1,3}(?:-[a-z]+(?:\.\d+)?)?|canary|other|[a-z0-9]+)$/');
        }
    });
});

it('sends other for a version a fork made up, and only digits for PHP', function () use ($build) {
    config(['app.version' => '4.7.0-acme-hosting']);

    $report = $build();

    expect($report['panel']['version'])->toBe('other')
        ->and($report['panel']['php'])->toMatch('/^\d+\.\d+\.\d+$/');
});

it('keeps release and source build versions as they are', function (string $version) use ($build) {
    config(['app.version' => $version]);

    expect($build()['panel']['version'])->toBe($version);
})->with(['4.7.0', 'v4.7.0', '4.7.0-rc.4', 'canary']);

it('names the database engine and its version in digits', function () use ($build) {
    $panel = $build()['panel'];

    expect($panel['database'])->toBeIn(['mysql', 'mariadb', 'pgsql', 'sqlite'])
        ->and($panel['database_version'])->toMatch('/^\d+\.\d+(?:\.\d+){0,2}$/');
});

it('counts accounts and the distinct accounts active in each window', function () use ($build) {
    User::factory()->create(['last_active_at' => now()->subHours(2)]);
    User::factory()->create(['last_active_at' => now()->subDays(3)]);
    User::factory()->create(['last_active_at' => now()->subDays(20)]);
    User::factory()->create(['last_active_at' => now()->subDays(45)]);
    User::factory()->create(['last_active_at' => null]);
    User::factory()->create(['root_admin' => true, 'last_active_at' => now()->subMinutes(5)]);
    User::factory()->create(['root_admin' => true, 'last_active_at' => now()->subDays(10)]);

    $total = User::query()->count();
    $admins = User::query()->where('root_admin', true)->count();

    expect($build()['users'])->toBe([
        'total' => $total,
        'admins' => $admins,
        'active' => ['day' => 2, 'week' => 3, 'month' => 5],
        'active_admins' => ['day' => 1, 'week' => 1, 'month' => 2],
    ]);
});

it('counts addresses by version and assignment, and tokens by kind', function () use ($build) {
    $before = $build()['resources'];

    [$user, , , $server] = createServerModel();
    $pool = AddressPool::factory()->create();
    Address::factory()->create(['address_pool_id' => $pool->id, 'type' => 'ipv4', 'address' => '192.0.2.10', 'server_id' => $server->id]);
    Address::factory()->create(['address_pool_id' => $pool->id, 'type' => 'ipv4', 'address' => '192.0.2.11', 'server_id' => null]);
    Address::factory()->create(['address_pool_id' => $pool->id, 'type' => 'ipv6', 'address' => '2001:db8::10', 'cidr' => 64, 'server_id' => null]);
    $user->createToken('cli', ApiKeyType::ACCOUNT);
    $user->createToken('billing', ApiKeyType::APPLICATION);
    $user->createToken('billing-2', ApiKeyType::APPLICATION);

    $after = $build()['resources'];

    expect($after['address_pools'] - $before['address_pools'])->toBe(1)
        ->and($after['addresses.ipv4'] - $before['addresses.ipv4'])->toBe(2)
        ->and($after['addresses.ipv6'] - $before['addresses.ipv6'])->toBe(1)
        ->and($after['addresses.assigned'] - $before['addresses.assigned'])->toBe(1)
        ->and($after['api_tokens.account'] - $before['api_tokens.account'])->toBe(1)
        ->and($after['api_tokens.application'] - $before['api_tokens.application'])->toBe(2)
        ->and($after['locations'] - $before['locations'])->toBe(1);
});

it('reports the pruning and throttle switches from the configuration', function () use ($build) {
    config(['activity.prune_days' => 0, 'backups.prune_age' => 360, 'backups.throttles.period' => 0]);

    expect($build()['features'])->toMatchArray([
        'activity.pruning' => false,
        'backups.pruning' => true,
        'backups.throttle' => false,
    ]);
});

it('caps a count at what the console takes', function () use ($build) {
    app(ViewCounter::class)->record('index', 20);
    Cache::increment('telemetry:views:index', 30_000_000);

    expect($build()['views'])->toBe(['index' => TelemetryReportBuilder::MAX_COUNT]);
});

it('documents every resource and feature key it sends', function () use ($build) {
    $docs = file_get_contents(base_path('docs/telemetry.md'));
    $report = $build();

    foreach ([...array_keys($report['resources']), ...array_keys($report['features'])] as $key) {
        expect($docs)->toContain("`{$key}`");
    }
});
