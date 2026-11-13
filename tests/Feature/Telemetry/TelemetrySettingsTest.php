<?php

use Carbon\CarbonImmutable;
use Convoy\Jobs\Telemetry\EraseTelemetryJob;
use Convoy\Models\TelemetrySetting;
use Convoy\Models\User;
use Convoy\Services\Telemetry\TelemetryClient;
use Convoy\Services\Telemetry\ViewCounter;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Support\TelemetryContract;

beforeEach(function () {
    Cache::flush();
    config([
        'convoy.telemetry.default' => true,
        'convoy.telemetry.enabled' => true,
        'convoy.telemetry.url' => 'https://console.test/api/v1/telemetry',
    ]);

    TelemetrySetting::current()->update(['enabled' => null, 'attempted_at' => null, 'reported_at' => null]);

    $this->admin = User::factory()->create(['root_admin' => true]);
});

it('shows whether reports go out', function () {
    $this->actingAs($this->admin)
        ->getJson('/api/admin/settings/telemetry')
        ->assertOk()
        ->assertExactJson(['data' => ['enabled' => true, 'disabled_by' => null]]);
});

it('names the environment variable that turned telemetry off', function (array $config, string $variable) {
    config($config);

    $this->actingAs($this->admin)
        ->getJson('/api/admin/settings/telemetry')
        ->assertOk()
        ->assertJsonPath('data.enabled', false)
        ->assertJsonPath('data.disabled_by', $variable);
})->with([
    [['convoy.telemetry.enabled' => false], 'CONVOY_TELEMETRY_ENABLED'],
    [['convoy.telemetry.url' => ''], 'CONVOY_TELEMETRY_URL'],
]);

it('erases the console\'s copy and starts a new installation when turned off', function () {
    $installation = TelemetrySetting::current()->installation_id;
    TelemetrySetting::current()->update(['reported_at' => CarbonImmutable::now()->subDay()]);
    app(ViewCounter::class)->record('index', 3);

    $this->actingAs($this->admin)
        ->putJson('/api/admin/settings/telemetry', ['enabled' => false])
        ->assertOk()
        ->assertJsonPath('data.enabled', false);

    $after = TelemetrySetting::current();

    Queue::assertPushed(EraseTelemetryJob::class, fn (EraseTelemetryJob $job) => $job->installation === $installation);

    expect($after->enabled)->toBeFalse()
        ->and($after->installation_id)->not->toBe($installation)
        ->and(Str::isUuid($after->installation_id))->toBeTrue()
        ->and($after->reported_at)->toBeNull()
        ->and(app(ViewCounter::class)->peek())->toBe([]);
});

it('does not erase again when it is saved off a second time', function () {
    $this->actingAs($this->admin)->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertOk();
    $installation = TelemetrySetting::current()->installation_id;

    $this->actingAs($this->admin)->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertOk();

    Queue::assertPushed(EraseTelemetryJob::class, 1);
    expect(TelemetrySetting::current()->installation_id)->toBe($installation);
});

it('turns back on under the new installation id', function () {
    $this->actingAs($this->admin)->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertOk();
    $installation = TelemetrySetting::current()->installation_id;

    $this->actingAs($this->admin)
        ->putJson('/api/admin/settings/telemetry', ['enabled' => true])
        ->assertOk()
        ->assertJsonPath('data.enabled', true);

    expect(TelemetrySetting::current()->enabled)->toBeTrue()
        ->and(TelemetrySetting::current()->installation_id)->toBe($installation);
});

it('turns on an opt-in panel', function () {
    config(['convoy.telemetry.default' => false]);

    $this->actingAs($this->admin)
        ->putJson('/api/admin/settings/telemetry', ['enabled' => true])
        ->assertOk()
        ->assertJsonPath('data.enabled', true);

    expect(TelemetrySetting::current()->enabled)->toBeTrue();
    Queue::assertNothingPushed();
});

it('cannot be turned on over the environment', function () {
    config(['convoy.telemetry.enabled' => false]);

    $this->actingAs($this->admin)
        ->putJson('/api/admin/settings/telemetry', ['enabled' => true])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('enabled');
});

it('asks the console to erase the installation', function () {
    Http::fake(['console.test/*' => Http::response(null, 204)]);

    (new EraseTelemetryJob('6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51'))->handle(app(TelemetryClient::class));

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && $request->url() === 'https://console.test/api/v1/telemetry/6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51'
        && $request->hasHeader('Accept', 'application/json'));
});

it('retries the erase when the console does not confirm it', function () {
    Http::fake(['console.test/*' => Http::response(null, 503)]);

    expect(fn () => (new EraseTelemetryJob('6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51'))->handle(app(TelemetryClient::class)))
        ->toThrow(RequestException::class);
});

it('shows the report it would send, without resetting anything', function () {
    Http::fake();
    app(ViewCounter::class)->record('admin.nodes.index', 2);

    $first = $this->actingAs($this->admin)->getJson('/api/admin/settings/telemetry/report')->assertOk()->json('data');
    $second = $this->actingAs($this->admin)->getJson('/api/admin/settings/telemetry/report')->assertOk()->json('data');

    expect(TelemetryContract::errors($first))->toBe([])
        ->and($first['installation'])->toBe(TelemetrySetting::current()->installation_id)
        ->and($first['views'])->toBe(['admin.nodes.index' => 2])
        ->and($second['views'])->toBe(['admin.nodes.index' => 2])
        ->and(TelemetrySetting::current()->reported_at)->toBeNull();

    Http::assertNothingSent();
});

it('is only for admins', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/admin/settings/telemetry')->assertForbidden();
    $this->actingAs($user)->getJson('/api/admin/settings/telemetry/report')->assertForbidden();
    $this->actingAs($user)->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertForbidden();
});
