<?php

use App\Enums\Audit\AuditEvent;
use App\Jobs\Telemetry\EraseTelemetryJob;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Telemetry\TelemetryClient;
use App\Services\Telemetry\ViewCounter;
use App\Settings\TelemetrySettings;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Support\TelemetryContract;

beforeEach(function () {
    config([
        'convoy.telemetry.enabled' => true,
        'convoy.telemetry.url' => 'https://console.test/api/v1/telemetry',
    ]);
});

$settings = fn () => app(TelemetrySettings::class)->refresh();

it('shows whether reports go out', function () {
    $this->actingAs(admin())
        ->getJson('/api/admin/settings/telemetry')
        ->assertOk()
        ->assertExactJson(['data' => ['enabled' => true, 'disabledBy' => null]]);
});

it('names the environment variable that turned telemetry off', function (array $config, string $variable) {
    config($config);

    $this->actingAs(admin())
        ->getJson('/api/admin/settings/telemetry')
        ->assertOk()
        ->assertJsonPath('data.enabled', false)
        ->assertJsonPath('data.disabledBy', $variable);
})->with([
    [['convoy.telemetry.enabled' => false], 'CONVOY_TELEMETRY_ENABLED'],
    [['convoy.telemetry.url' => ''], 'CONVOY_TELEMETRY_URL'],
]);

it('erases the console\'s copy and starts a new installation when turned off', function () use ($settings) {
    $before = $settings();
    $installation = $before->installation_id;
    $before->reported_at = CarbonImmutable::now()->subDay();
    $before->save();
    app(ViewCounter::class)->record('index', 3);

    $this->actingAs(admin())
        ->putJson('/api/admin/settings/telemetry', ['enabled' => false])
        ->assertOk()
        ->assertJsonPath('data.enabled', false);

    $after = $settings();

    Queue::assertPushed(EraseTelemetryJob::class, fn (EraseTelemetryJob $job) => $job->installation === $installation);

    expect($after->enabled)->toBeFalse()
        ->and($after->installation_id)->not->toBe($installation)
        ->and(Str::isUuid($after->installation_id))->toBeTrue()
        ->and($after->reported_at)->toBeNull()
        ->and($after->counting_since)->toBeNull()
        ->and(app(ViewCounter::class)->peek())->toBe([])
        ->and(AuditLog::query()->forEvent(AuditEvent::ADMIN_SETTINGS_TELEMETRY_UPDATED)->sole()->properties->all())
        ->toBe(['enabled' => false]);
});

it('does not erase again when it is saved off a second time', function () use ($settings) {
    $this->actingAs(admin())->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertOk();
    $installation = $settings()->installation_id;

    $this->actingAs(admin())->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertOk();

    Queue::assertPushed(EraseTelemetryJob::class, 1);
    expect($settings()->installation_id)->toBe($installation);
});

it('turns back on under the new installation id', function () use ($settings) {
    $this->actingAs(admin())->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertOk();
    $installation = $settings()->installation_id;

    $this->actingAs(admin())
        ->putJson('/api/admin/settings/telemetry', ['enabled' => true])
        ->assertOk()
        ->assertJsonPath('data.enabled', true);

    expect($settings()->enabled)->toBeTrue()
        ->and($settings()->installation_id)->toBe($installation)
        // Nothing from the time it was off is ever reported.
        ->and($settings()->counting_since?->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('cannot be turned on over the environment', function () {
    config(['convoy.telemetry.enabled' => false]);

    $this->actingAs(admin())
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
    app(ViewCounter::class)->record('admin.nodes.index', 2);

    $first = $this->actingAs(admin())->getJson('/api/admin/settings/telemetry/report')->assertOk()->json('data');
    $second = $this->actingAs(admin())->getJson('/api/admin/settings/telemetry/report')->assertOk()->json('data');

    expect(TelemetryContract::errors($first))->toBe([])
        ->and($first['views'])->toBe(['admin.nodes.index' => 2])
        ->and($second['views'])->toBe(['admin.nodes.index' => 2])
        ->and(app(TelemetrySettings::class)->refresh()->reported_at)->toBeNull();

    Http::assertNothingSent();
});

it('is only for admins', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/admin/settings/telemetry')->assertForbidden();
    $this->actingAs($user)->getJson('/api/admin/settings/telemetry/report')->assertForbidden();
    $this->actingAs($user)->putJson('/api/admin/settings/telemetry', ['enabled' => false])->assertForbidden();
});
