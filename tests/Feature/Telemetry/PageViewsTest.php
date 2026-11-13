<?php

use Convoy\Http\Requests\Client\RecordPageViewsRequest;
use Convoy\Models\TelemetrySetting;
use Convoy\Models\User;
use Convoy\Services\Telemetry\PageKeys;
use Convoy\Services\Telemetry\TelemetryKey;
use Convoy\Services\Telemetry\ViewCounter;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    config([
        'convoy.telemetry.default' => true,
        'convoy.telemetry.enabled' => true,
        'convoy.telemetry.url' => 'https://console.test/api/v1/telemetry',
    ]);

    TelemetrySetting::current()->update(['enabled' => null]);
});

it('gives every listed page a valid key of its own', function () {
    $keys = array_values(PageKeys::PAGES);

    expect(array_filter($keys, fn (string $key) => ! TelemetryKey::valid($key)))->toBe([])
        ->and(array_unique($keys))->toHaveCount(count($keys));
});

it('lists every page the frontend routes to', function () {
    // Every `path: '...'` in the route definitions has to end up in a listed pattern: as the whole
    // pattern for an absolute path, as one of its segments for a relative one. A new page that
    // isn't listed would never be counted.
    $patterns = array_keys(PageKeys::PAGES);
    // Deliberately not counted: the sign-in screen and the "page not found" catch-all.
    $ignored = ['/auth', 'login', '*'];
    $missing = [];

    foreach (glob(resource_path('scripts/routers/*.tsx')) as $file) {
        preg_match_all("/\bpath:\s*'([^']+)'/", file_get_contents($file), $paths);

        foreach (array_diff($paths[1], $ignored) as $path) {
            $listed = str_starts_with($path, '/')
                ? collect($patterns)->contains(fn (string $pattern) => $pattern === $path || str_starts_with($pattern, $path.'/'))
                : collect($patterns)->contains(fn (string $pattern) => in_array($path, explode('/', $pattern), true)
                    || str_contains($pattern, "/{$path}/"));

            if (! $listed) {
                $missing[] = basename($file).": {$path}";
            }
        }
    }

    expect($missing)->toBe([]);
});

it('counts the pages the browser reports', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', [
            '/servers/:id/backups' => 3,
            '/' => 1,
            '/admin/nodes/:nodeId' => 2,
        ])
        ->assertNoContent();

    expect(app(ViewCounter::class)->peek())->toBe([
        'admin.nodes.show' => 2,
        'index' => 1,
        'servers.backups' => 3,
    ]);
});

it('drops patterns the frontend does not have, the sign-in screen and redirect-only routes', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', [
            '/servers/jane-doe-was-here' => 5,
            'servers.backups' => 5,
            '/auth/login' => 1,
            '/admin/servers/:id/settings' => 1,
            '*' => 1,
            '/admin/nodes' => 2,
        ])
        ->assertNoContent();

    expect(app(ViewCounter::class)->peek())->toBe(['admin.nodes.index' => 2]);
});

it('caps what one batch can add to a page', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', ['/' => 1_000_000])
        ->assertNoContent();

    expect(app(ViewCounter::class)->peek())->toBe(['index' => RecordPageViewsRequest::MAX_VIEWS_PER_PAGE]);
});

it('refuses a batch with more pages than a tab opens', function () {
    $batch = collect(range(1, RecordPageViewsRequest::MAX_PAGES + 1))
        ->mapWithKeys(fn (int $page) => ["/page-{$page}" => 1])
        ->all();

    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', $batch)
        ->assertUnprocessable();
});

it('refuses a body that is not an object of counts', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', [3, 4])
        ->assertUnprocessable();
});

it('refuses counts that are not whole numbers above zero', function (mixed $count) {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', ['/' => $count])
        ->assertUnprocessable();

    expect(app(ViewCounter::class)->peek())->toBe([]);
})->with([0, -1, 'many', 1.5]);

it('limits how often one account can send', function () {
    $user = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($user)->postJson('/api/client/telemetry/page-views', ['/' => 1])->assertNoContent();
    }

    $this->actingAs($user)->postJson('/api/client/telemetry/page-views', ['/' => 1])->assertTooManyRequests();

    expect(app(ViewCounter::class)->peek())->toBe(['index' => 10]);
});

it('counts nothing while telemetry is off', function (array $config, ?bool $setting) {
    config($config);
    TelemetrySetting::current()->update(['enabled' => $setting]);

    $this->actingAs(User::factory()->create())
        ->postJson('/api/client/telemetry/page-views', ['/' => 1])
        ->assertNoContent();

    expect(app(ViewCounter::class)->peek())->toBe([]);
})->with([
    'admin switch' => [[], false],
    'environment switch' => [['convoy.telemetry.enabled' => false], null],
]);

it('refuses an API token, since a script is not someone using the panel', function () {
    $user = User::factory()->create(['root_admin' => true]);
    $token = $user->createToken('cli', Convoy\Enums\Api\ApiKeyType::ACCOUNT)->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/client/telemetry/page-views', ['/' => 1])
        ->assertUnauthorized();

    expect(app(ViewCounter::class)->peek())->toBe([]);
});

it('refuses a signed-out request', function () {
    $this->postJson('/api/client/telemetry/page-views', ['/' => 1])
        ->assertUnauthorized();
});
