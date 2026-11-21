<?php

use App\Enums\Audit\AuditEvent;
use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use App\Jobs\Telemetry\EraseTelemetryJob;
use App\Jobs\Telemetry\SendTelemetryReportJob;
use App\Models\AuditLog;
use App\Services\Telemetry\TelemetryService;
use App\Services\Telemetry\ViewCounter;
use App\Settings\TelemetrySettings;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\Support\TelemetryContract;

const TELEMETRY_URL = 'https://console.test/api/v1/telemetry';

beforeEach(function () {
    config([
        'app.version' => '5.0.2',
        'convoy.telemetry.enabled' => true,
        'convoy.telemetry.url' => TELEMETRY_URL,
    ]);

    $this->travelTo(CarbonImmutable::parse('2026-10-23 14:37:00', 'UTC'));

    $settings = app(TelemetrySettings::class);
    $settings->counting_since = CarbonImmutable::now()->subDays(2);
    $settings->save();
});

$settings = fn () => app(TelemetrySettings::class)->refresh();

// Called directly: the suite fakes the queue, which would swallow a dispatchSync.
$send = fn () => app()->call([new SendTelemetryReportJob, 'handle']);

$logins = fn (int $count, CarbonImmutable $at) => collect(range(1, $count))->each(
    fn () => AuditLog::query()->create(['event' => AuditEvent::AUTH_LOGIN_SUCCEEDED, 'properties' => [], 'created_at' => $at]),
);

it('holds a lock that drops a second copy and expires just after the job times out', function () {
    $job = new SendTelemetryReportJob;
    [$lock] = $job->middleware();

    expect($lock)->toBeInstanceOf(ExpiringWithoutOverlapping::class)
        ->and($lock->releaseAfter)->toBeNull()
        ->and($lock->expiresAfter)->toBe($job->timeout + ExpiringWithoutOverlapping::EXPIRY_MARGIN);
});

it('sends the report and moves the window on once the console accepts it', function () use ($settings, $send) {
    Http::fake([TELEMETRY_URL => Http::response(null, 204)]);
    app(ViewCounter::class)->record('admin.nodes.index', 4);

    $send();

    Http::assertSent(function (Request $request) use ($settings) {
        return $request->url() === TELEMETRY_URL
            && $request->method() === 'POST'
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('User-Agent', 'Convoy/5.0.2')
            && $request['installation'] === $settings()->installation_id
            && $request['views'] === ['admin.nodes.index' => 4]
            && TelemetryContract::errors($request->data()) === [];
    });

    expect($settings()->reported_at?->toIso8601String())->toBe(now()->toIso8601String())
        ->and(app(ViewCounter::class)->peek())->toBe([]);
});

it('sends at most one report a UTC day', function () use ($send) {
    Http::fake([TELEMETRY_URL => Http::response(null, 204)]);

    $send();
    $this->travel(9)->hours(); // 23:37, still the 23rd
    $send();

    Http::assertSentCount(1);

    $this->travel(1)->hour(); // 00:37 on the 24th
    $send();

    Http::assertSentCount(2);
});

it('counts every audit event in exactly one report', function () use ($logins, $send) {
    Http::fake([TELEMETRY_URL => Http::response(null, 204)]);

    $logins(2, CarbonImmutable::now()->subHours(3));
    $send();

    $logins(5, CarbonImmutable::now()->addHours(6));
    $this->travel(1)->day();
    $send();

    $sent = Http::recorded()->map(fn (array $pair) => $pair[0]['activity']);

    expect($sent->all())->toBe([
        ['auth.login.succeeded' => 2],
        ['auth.login.succeeded' => 5],
    ]);
});

it('keeps the counts and waits a day when the console refuses the report', function () use ($settings, $logins, $send) {
    Http::fake([TELEMETRY_URL => Http::sequence()
        ->push(['errors' => ['nodes' => ['The nodes field must be a list.']]], 422)
        ->push(null, 204)]);
    Log::spy();
    app(ViewCounter::class)->record('index', 3);
    $logins(1, CarbonImmutable::now()->subHours(20));

    $send();

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context) => $context['status'] === 422);

    expect($settings()->reported_at)->toBeNull()
        ->and(app(ViewCounter::class)->peek())->toBe(['index' => 3]);

    // The next day's report still carries everything since counting started, including the
    // login more than a day before it.
    $this->travel(1)->day();
    $send();

    $retry = Http::recorded()[1][0];

    expect($retry['views'])->toBe(['index' => 3])
        ->and($retry['activity'])->toBe(['auth.login.succeeded' => 1])
        ->and($settings()->reported_at)->not->toBeNull();
});

it('logs a warning when the console cannot be reached', function () use ($settings, $send) {
    Http::fake([TELEMETRY_URL => fn () => throw new ConnectionException('cURL error 28: timed out')]);
    Log::shouldReceive('warning')->once()->withArgs(fn (string $message, array $context) => str_contains($context['error'], 'timed out'));

    $send();

    expect($settings()->reported_at)->toBeNull();
});

it('keeps views recorded while the report was in flight', function () use ($send) {
    Http::fake([TELEMETRY_URL => function () {
        app(ViewCounter::class)->record('index', 2);

        return Http::response(null, 204);
    }]);
    app(ViewCounter::class)->record('index', 5);

    $send();

    expect(app(ViewCounter::class)->peek())->toBe(['index' => 2]);
});

it('erases the report again if telemetry was turned off while it was in flight', function () use ($settings, $send) {
    $installation = $settings()->installation_id;
    Http::fake([TELEMETRY_URL => function () {
        app(TelemetryService::class)->disable();

        return Http::response(null, 204);
    }]);
    app(ViewCounter::class)->record('index', 5);

    $send();

    Queue::assertPushed(EraseTelemetryJob::class, 2);
    Queue::assertPushed(EraseTelemetryJob::class, fn (EraseTelemetryJob $job) => $job->installation === $installation);

    app(ViewCounter::class)->record('index');

    expect($settings()->reported_at)->toBeNull()
        ->and(app(ViewCounter::class)->peek())->toBe(['index' => 1]);
});

it('sends nothing while telemetry is off', function (array $config, bool $setting) use ($send) {
    Http::fake();
    config($config);
    $settings = app(TelemetrySettings::class);
    $settings->enabled = $setting;
    $settings->save();

    $send();

    Http::assertNothingSent();
})->with([
    'admin switch' => [[], false],
    'environment switch' => [['convoy.telemetry.enabled' => false], true],
    'no URL' => [['convoy.telemetry.url' => ''], true],
]);

it('picks a minute of the UTC day from the installation id', function () use ($settings) {
    $stored = $settings();
    $stored->installation_id = '00000a00-0000-4000-8000-000000000000';
    $stored->save();

    $telemetry = app(TelemetryService::class);
    $minute = 0xA00 % 1440; // 1120: 18:40 UTC

    expect($telemetry->minuteOfDay())->toBe($minute)
        ->and($telemetry->isDue(CarbonImmutable::parse('2026-10-23 18:40:00', 'UTC')))->toBeTrue()
        ->and($telemetry->isDue(CarbonImmutable::parse('2026-10-23 20:40:00', 'Europe/Berlin')))->toBeTrue()
        ->and($telemetry->isDue(CarbonImmutable::parse('2026-10-23 18:41:00', 'UTC')))->toBeFalse();
});

it('schedules the report every minute, gated on the install\'s own minute', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => $event->description === 'telemetry:report');

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *');
});
