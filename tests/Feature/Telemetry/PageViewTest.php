<?php

use Convoy\Models\Setting;
use Convoy\Models\User;
use Convoy\Services\Telemetry\PageViewCounter;
use Convoy\Services\Telemetry\TelemetryReportService;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

$url = 'https://console.test/api/v1/telemetry';

beforeEach(function () use ($url) {
    config(['convoy.telemetry.enabled' => true, 'convoy.telemetry.url' => $url]);

    Setting::forget(TelemetryService::DISABLED);
    app(PageViewCounter::class)->flush();
});

it('counts the pages a signed-in user opens, by name', function () {
    $user = User::factory()->create();

    foreach (['/servers/:id', '/servers/:id', '/servers/:id/backups', '/admin/nodes/:nodeId/isos'] as $page) {
        $this->actingAs($user)->postJson('/api/client/telemetry/views', ['page' => $page])->assertNoContent();
    }

    expect(app(PageViewCounter::class)->counts())->toBe([
        'client.servers.show' => 2,
        'client.servers.backups' => 1,
        'admin.nodes.isos' => 1,
    ]);
});

it('ignores a page it does not know', function (string $page) {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/views', ['page' => $page])
        ->assertNoContent();

    expect(app(PageViewCounter::class)->counts())->toBe([]);
})->with([
    'an address' => ['/servers/3f2a91bc/backups'],
    'free text' => ['jane@example.com'],
    'a layout' => ['/servers/:id/settings'],
    'not found' => ['/*'],
]);

it('counts nothing while telemetry is off', function () {
    app(TelemetryService::class)->disable();

    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/views', ['page' => '/servers/:id'])
        ->assertNoContent();

    expect(app(PageViewCounter::class)->counts())->toBe([]);
});

it('takes no views from guests', function () {
    $this->postJson('/api/client/telemetry/views', ['page' => '/servers/:id'])->assertUnauthorized();

    expect(app(PageViewCounter::class)->counts())->toBe([]);
});

it('sends the views since the last report, then starts counting again', function () use ($url) {
    Http::fake([$url => Http::response(null, 204), '*' => Http::response(['data' => []])]);
    $views = app(PageViewCounter::class);

    $views->record('/admin');
    $views->record('/admin');

    runTelemetryReportJob();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && (array) $request['views'] === ['admin.overview' => 2]);
    expect($views->counts())->toBe([]);

    $views->record('/admin');

    expect($views->flush())->toBe(['admin.overview' => 1])
        ->and($views->counts())->toBe([]);
});

it('shows the views without taking them off the counters', function () {
    Http::fake(['*' => Http::response(['data' => []])]);
    app(PageViewCounter::class)->record('/');

    $report = app(TelemetryReportService::class)->build('6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51');

    expect((array) $report['views'])->toBe(['client.servers.index' => 1])
        ->and(app(PageViewCounter::class)->counts())->toBe(['client.servers.index' => 1])
        ->and(validateTelemetryReport($report)->errors()->all())->toBe([]);
});

it('sends an empty map, not a list, when no page was opened', function () {
    Http::fake(['*' => Http::response(['data' => []])]);

    $report = app(TelemetryReportService::class)->build('6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51');

    expect(json_encode($report))->toContain('"views":{}');
});

it('tells the router whether to report page views', function (bool $enabled) {
    config(['convoy.telemetry.enabled' => $enabled]);

    $this->withoutVite()->get('/')->assertOk()->assertSee('"telemetry":'.($enabled ? 'true' : 'false'), false);
})->with([true, false]);

it('lists only names the console accepts', function () {
    foreach (PageViewCounter::PAGES as $pattern => $name) {
        expect($pattern)->toStartWith('/')
            ->and($name)->toMatch('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/');
    }

    expect(array_unique(PageViewCounter::PAGES))->toHaveCount(count(PageViewCounter::PAGES));
});
