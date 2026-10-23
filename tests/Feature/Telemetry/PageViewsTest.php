<?php

use App\Http\Requests\Client\RecordPageViewsRequest;
use App\Models\User;
use App\Services\Api\CreateAccountTokenService;
use App\Services\Telemetry\PageKeys;
use App\Services\Telemetry\ViewCounter;
use App\Settings\TelemetrySettings;

beforeEach(function () {
    $this->app->instance(PageKeys::class, new PageKeys(base_path('tests/Fixtures/Telemetry/routeTree.gen.ts')));
});

it('names a page after its route, without its parameters', function (string $routeId, ?string $key) {
    expect(PageKeys::fromRouteId($routeId))->toBe($key);
})->with([
    'page under a record' => ['/_app/servers/$serverUuid/backups', 'servers.backups'],
    'a record\'s own page' => ['/_app/servers/$serverUuid/', 'servers.show'],
    'detail page that is a leaf' => ['/_app/admin/_dashboard/anchors/$anchorId', 'admin.anchors.show'],
    'list under a pathless layout' => ['/_app/admin/_dashboard/nodes/', 'admin.nodes.index'],
    'detail of the same list' => ['/_app/admin/nodes/$nodeId/', 'admin.nodes.show'],
    'route group' => ['/_app/(fullscreen)/servers/$serverUuid/console', 'servers.console'],
    'dashboard' => ['/_app/_dashboard/', 'index'],
    'sign-in screen' => ['/auth/(login)/login', null],
]);

it('gives every page in the built frontend its own key', function () {
    $pages = new PageKeys;
    $ids = $pages->routeIds();

    if ($ids === []) {
        $this->markTestSkipped('The frontend has not been built, so there is no route tree to read.');
    }

    // A leaf is an index route, or a route no other route nests under.
    $leaves = array_filter($ids, fn (string $id) => str_ends_with($id, '/')
        || collect($ids)->doesntContain(fn (string $other) => str_starts_with($other, $id.'/')));

    $keys = collect($leaves)
        ->mapWithKeys(fn (string $id) => [$id => PageKeys::fromRouteId($id)])
        ->filter();

    expect($keys)->not->toBeEmpty()
        ->and($keys->duplicates()->all())->toBe([])
        ->and($keys->keys()->filter(fn (string $id) => str_starts_with($id, '/_app/'))->count())->toBe($keys->count());
});

it('reads the route ids from the generated route tree', function () {
    expect(app(PageKeys::class)->routeIds())->toBe([
        '/_app',
        '/auth/(login)/login',
        '/_app/_dashboard/',
        '/_app/servers/$serverUuid/backups',
        '/_app/admin/_dashboard/nodes/',
        '/_app/admin/nodes/$nodeId/',
        '/_app/(fullscreen)/servers/$serverUuid/console',
    ]);
});

it('counts no pages when the route tree has not been built', function () {
    expect((new PageKeys(base_path('tests/Fixtures/Telemetry/missing.ts')))->for('/_app/_dashboard/'))->toBeNull();
});

it('counts the pages the browser reports', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', [
            '/_app/servers/$serverUuid/backups' => 3,
            '/_app/_dashboard/' => 1,
            '/_app/admin/nodes/$nodeId/' => 2,
        ])
        ->assertNoContent();

    expect(app(ViewCounter::class)->peek())->toBe([
        'admin.nodes.show' => 2,
        'index' => 1,
        'servers.backups' => 3,
    ]);
});

it('drops route ids the frontend does not have, and the sign-in screens', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', [
            '/_app/jane-doe-was-here' => 5,
            'servers.backups' => 5,
            '/auth/(login)/login' => 1,
            '/_app/admin/_dashboard/nodes/' => 2,
        ])
        ->assertNoContent();

    expect(app(ViewCounter::class)->peek())->toBe(['admin.nodes.index' => 2]);
});

it('caps what one batch can add to a page', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', ['/_app/_dashboard/' => 1_000_000])
        ->assertNoContent();

    expect(app(ViewCounter::class)->peek())->toBe(['index' => RecordPageViewsRequest::MAX_VIEWS_PER_PAGE]);
});

it('refuses a batch with more pages than a tab opens', function () {
    $batch = collect(range(1, RecordPageViewsRequest::MAX_PAGES + 1))
        ->mapWithKeys(fn (int $page) => ["/_app/page-{$page}" => 1])
        ->all();

    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', $batch)
        ->assertUnprocessable();
});

it('refuses counts that are not whole numbers above zero', function (mixed $count) {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', ['/_app/_dashboard/' => $count])
        ->assertUnprocessable();

    expect(app(ViewCounter::class)->peek())->toBe([]);
})->with([0, -1, 'many', 1.5]);

it('limits how often one account can send', function () {
    $user = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($user)->postJson('/api/client/telemetry/page-views', ['/_app/_dashboard/' => 1])->assertNoContent();
    }

    $this->actingAs($user)->postJson('/api/client/telemetry/page-views', ['/_app/_dashboard/' => 1])->assertTooManyRequests();

    expect(app(ViewCounter::class)->peek())->toBe(['index' => 10]);
});

it('counts nothing while telemetry is off', function () {
    $settings = app(TelemetrySettings::class);
    $settings->enabled = false;
    $settings->save();

    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', ['/_app/_dashboard/' => 1])
        ->assertNoContent();

    expect(app(ViewCounter::class)->peek())->toBe([]);
});

it('refuses an API token, since a script is not someone using the panel', function () {
    $user = User::factory()->create();
    $token = app(CreateAccountTokenService::class)->handle($user, 'cli', ['servers:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/client/telemetry/page-views', ['/_app/_dashboard/' => 1])
        ->assertForbidden();

    expect(app(ViewCounter::class)->peek())->toBe([]);
});

it('refuses a signed-out request', function () {
    $this->postJson('/api/client/telemetry/page-views', ['/_app/_dashboard/' => 1])
        ->assertUnauthorized();
});
