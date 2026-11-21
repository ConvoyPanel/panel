<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AdminRole;
use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use App\Services\Telemetry\TelemetryReportBuilder;
use App\Services\Telemetry\ViewCounter;
use App\Settings\TelemetrySettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\TelemetryContract;

$build = fn () => app(TelemetryReportBuilder::class)->build(CarbonImmutable::now())->payload;

$event = fn (AuditEvent $event, CarbonImmutable $at) => AuditLog::query()->create([
    'event' => $event,
    'actor_label' => 'Jane Operator',
    'ip' => '198.51.100.23',
    'properties' => ['hostname' => 'customer-vm.example.net'],
    'created_at' => $at,
]);

beforeEach(function () {
    config(['app.version' => '5.0.2']);

    $settings = app(TelemetrySettings::class);
    $settings->counting_since = CarbonImmutable::now()->subDay();
    $settings->save();
});

it('builds a report the console accepts from a populated panel', function () use ($build, $event) {
    $node = Node::factory()->withAgent()->create([
        'fqdn' => 'pve1.secret-host.example.com',
        'display_name' => 'Frankfurt rack 4',
        'agent_reported_facts' => ['pve_version' => '8.4.1-pve', 'hostname' => 'pve1.secret-host.example.com'],
    ]);
    Server::factory()->count(2)->create(['node_id' => $node->id, 'hostname' => 'customer-vm.example.net']);
    User::factory()->create(['email' => 'jane@customer.example', 'name' => 'Jane Customer']);
    $event(AuditEvent::AUTH_LOGIN_SUCCEEDED, CarbonImmutable::now()->subHour());
    app(ViewCounter::class)->record('admin.nodes.show');

    $report = $build();

    expect(TelemetryContract::errors($report))->toBe([])
        ->and($report['nodes'])->toBe([[
            'proxmox' => '8.4.1',
            'agent' => '0.1.0',
            'cpus' => 32,
            'memory_gib' => 64,
            'servers' => 2,
        ]])
        ->and($report['activity'])->toBe(['auth.login.succeeded' => 1])
        ->and($report['views'])->toBe(['admin.nodes.show' => 1]);
});

it('carries no names, addresses, hostnames or secrets', function () use ($build, $event) {
    config([
        'app.url' => 'https://panel.secret-host.example.com',
        'app.name' => 'Acme Hosting Panel',
    ]);

    $node = Node::factory()->withAgent()->create([
        'fqdn' => 'pve1.secret-host.example.com',
        'display_name' => 'Frankfurt rack 4',
        'agent_reported_facts' => ['pve_version' => '8.4.1', 'hostname' => 'pve1.secret-host.example.com'],
    ]);
    Server::factory()->create(['node_id' => $node->id, 'hostname' => 'customer-vm.example.net', 'name' => 'Customer VM']);
    User::factory()->create(['email' => 'jane@customer.example', 'name' => 'Jane Customer', 'root_admin' => true]);
    $event(AuditEvent::ADMIN_NODE_UPDATED, CarbonImmutable::now()->subMinute());

    $json = json_encode($build());

    foreach ([
        'secret-host', 'example', 'Acme', 'Frankfurt', 'Jane', 'Customer', '198.51.100.23',
        config('app.key'), $node->agent_uuid, $node->agent_secret, 'license',
    ] as $needle) {
        expect($json)->not->toContain($needle);
    }

    // Every string in the report is a version, a slug, a UUID or a database name.
    $report = $build();
    array_walk_recursive($report, function (mixed $value) {
        if (is_string($value)) {
            expect($value)->toMatch('/^(?:[0-9a-f-]{36}|v?\d+(?:\.\d+){1,3}(?:-[a-z]+(?:\.\d+)?)?|canary|other|[a-z0-9]+)$/');
        }
    });
});

it('sends only digits for versions, and other for a version a fork made up', function () use ($build) {
    config(['app.version' => '5.0.2-acme-hosting']);
    Node::factory()->withAgent()->create([
        'agent_version' => 'v1.2.0-dirty',
        'agent_reported_facts' => ['pve_version' => 'pve-manager/8.4.1'],
    ]);

    $report = $build();

    expect($report['panel']['version'])->toBe('other')
        ->and($report['panel']['php'])->toMatch('/^\d+\.\d+\.\d+$/')
        ->and($report['nodes'][0]['agent'])->toBe('1.2.0')
        ->and($report['nodes'][0]['proxmox'])->toBeNull();
});

it('keeps release and source build versions as they are', function (string $version) use ($build) {
    config(['app.version' => $version]);

    expect($build()['panel']['version'])->toBe($version);
})->with(['5.0.2', 'v5.0.2', '5.1.0-rc.2', 'canary']);

it('counts accounts and the distinct accounts active in each window', function () use ($build) {
    User::factory()->create(['last_active_at' => now()->subHours(2)]);
    User::factory()->create(['last_active_at' => now()->subDays(3)]);
    User::factory()->create(['last_active_at' => now()->subDays(20)]);
    User::factory()->create(['last_active_at' => now()->subDays(45)]);
    User::factory()->create(['last_active_at' => null]);
    User::factory()->create(['root_admin' => true, 'last_active_at' => now()->subMinutes(5)]);
    // Any admin role counts, not only Superadmin.
    User::factory()->create([
        'admin_role_id' => AdminRole::query()->where('name', 'Support')->value('id'),
        'last_active_at' => now()->subDays(10),
    ]);

    expect($build()['users'])->toBe([
        'total' => 7,
        'admins' => 2,
        'active' => ['day' => 2, 'week' => 3, 'month' => 5],
        'active_admins' => ['day' => 1, 'week' => 1, 'month' => 2],
    ]);
});

it('counts audit events since the last accepted report, and only events the catalog knows', function () use ($build, $event) {
    $settings = app(TelemetrySettings::class);
    $settings->reported_at = CarbonImmutable::now()->subHours(6);
    $settings->save();

    $event(AuditEvent::SERVER_BACKUP_CREATED, CarbonImmutable::now()->subHours(7));
    $event(AuditEvent::SERVER_BACKUP_CREATED, CarbonImmutable::now()->subHours(5));
    $event(AuditEvent::SERVER_BACKUP_CREATED, CarbonImmutable::now()->subHours(1));
    $event(AuditEvent::AUTH_LOGIN_FAILED, CarbonImmutable::now()->subHours(1));
    DB::table('audit_logs')->insert(['event' => 'legacy.Something Free Text', 'properties' => '{}', 'created_at' => now()->subHour()]);

    expect($build()['activity'])->toBe([
        'auth.login.failed' => 1,
        'server.backup.created' => 2,
    ]);
});

it('counts audit events from when counting started in the first report', function () use ($build, $event) {
    $settings = app(TelemetrySettings::class);
    $settings->counting_since = CarbonImmutable::now()->subDays(2);
    $settings->save();

    $event(AuditEvent::AUTH_LOGOUT, CarbonImmutable::now()->subDays(3));
    $event(AuditEvent::AUTH_LOGOUT, CarbonImmutable::now()->subHours(30));

    expect($build()['activity'])->toBe(['auth.logout' => 1]);
});

it('reports which of the bundled OAuth providers are configured, never one an operator added', function () use ($build) {
    config([
        'oauth.providers.github.enabled' => true,
        'services.github.client_id' => 'abc',
        'oauth.providers.acme-internal-sso' => ['enabled' => true, 'label' => 'Acme SSO'],
        'services.acme-internal-sso.client_id' => 'xyz',
    ]);

    $features = $build()['features'];

    expect($features['oauth.github'])->toBeTrue()
        ->and($features['oauth.google'])->toBeFalse()
        ->and(json_encode($features))->not->toContain('acme');
});

it('documents every resource and feature key it sends', function () use ($build) {
    $docs = file_get_contents(base_path('docs/telemetry.md'));
    $report = $build();

    foreach ([...array_keys($report['resources']), ...array_keys($report['features'])] as $key) {
        expect($docs)->toContain("`{$key}`");
    }
});

// The console refuses a whole report with one value past its limits, so the
// builder caps instead of sending them (docs/telemetry.md, "General rules").
it('sends at most the 500 nodes the console accepts', function () use ($build) {
    $location = Location::factory()->create();
    Node::factory()->count(TelemetryReportBuilder::MAX_NODES + 1)->create(['location_id' => $location->id]);

    $report = $build();

    expect($report['nodes'])->toHaveCount(TelemetryReportBuilder::MAX_NODES)
        ->and(TelemetryContract::errors($report))->toBe([]);
});

it('caps counts and node figures at the console\'s limits', function () {
    Node::factory()->create(['cpu_count' => 50_000, 'memory' => 2_000_000 * 1024 ** 3]);
    app(ViewCounter::class)->record('admin.nodes.show', TelemetryReportBuilder::MAX_COUNT + 5);

    $built = app(TelemetryReportBuilder::class)->build(CarbonImmutable::now());

    expect(TelemetryContract::errors($built->payload))->toBe([])
        ->and($built->payload['nodes'][0]['cpus'])->toBe(10_000)
        ->and($built->payload['nodes'][0]['memory_gib'])->toBe(1_000_000)
        ->and($built->payload['views'])->toBe(['admin.nodes.show' => TelemetryReportBuilder::MAX_COUNT])
        // What the job takes off the counter once the console accepts: the
        // capped amount, so the rest is reported the next day.
        ->and($built->views)->toBe(['admin.nodes.show' => TelemetryReportBuilder::MAX_COUNT]);
});
