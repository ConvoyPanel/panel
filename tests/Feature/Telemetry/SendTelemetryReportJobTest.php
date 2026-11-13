<?php

use Carbon\CarbonImmutable;
use Convoy\Jobs\Telemetry\EraseTelemetryJob;
use Convoy\Jobs\Telemetry\SendTelemetryReportJob;
use Convoy\Models\TelemetrySetting;
use Convoy\Services\Telemetry\TelemetryService;
use Convoy\Services\Telemetry\ViewCounter;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\Support\TelemetryContract;

const TELEMETRY_URL = 'https://console.test/api/v1/telemetry';

beforeEach(function () {
    Cache::flush();
    config([
        'app.version' => '4.7.0',
        'convoy.telemetry.enabled' => true,
        'convoy.telemetry.url' => TELEMETRY_URL,
    ]);

    $this->travelTo(CarbonImmutable::parse('2026-10-23 14:37:00', 'UTC'));

    TelemetrySetting::current()->update(['enabled' => null, 'attempted_at' => null, 'reported_at' => null]);
});

// Called directly: the suite fakes the queue, which would swallow a dispatchSync.
$send = fn () => app()->call([new SendTelemetryReportJob, 'handle']);

it('sends the report and takes the views it carried off the counters once the console accepts it', function () use ($send) {
    Http::fake([TELEMETRY_URL => Http::response(null, 204)]);
    app(ViewCounter::class)->record('admin.nodes.index', 4);

    $send();

    Http::assertSent(function (Request $request) {
        return $request->url() === TELEMETRY_URL
            && $request->method() === 'POST'
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('User-Agent', 'Convoy/4.7.0')
            && $request['installation'] === TelemetrySetting::current()->installation_id
            && $request['views'] === ['admin.nodes.index' => 4]
            && TelemetryContract::errors($request->data()) === [];
    });

    expect(TelemetrySetting::current()->reported_at?->toIso8601String())->toBe(now()->toIso8601String())
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

it('keeps the counts and waits a day when the console refuses the report', function () use ($send) {
    Http::fake([TELEMETRY_URL => Http::sequence()
        ->push(['errors' => ['nodes' => ['The nodes field must be a list.']]], 422)
        ->push(null, 204)]);
    Log::spy();
    app(ViewCounter::class)->record('index', 3);

    $send();

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context) => $context['status'] === 422);

    expect(TelemetrySetting::current()->reported_at)->toBeNull()
        ->and(app(ViewCounter::class)->peek())->toBe(['index' => 3]);

    // Not again the same day: a refused report is not retried.
    $this->travel(1)->hour();
    $send();
    Http::assertSentCount(1);

    $this->travel(1)->day();
    $send();

    expect(Http::recorded()[1][0]['views'])->toBe(['index' => 3])
        ->and(TelemetrySetting::current()->reported_at)->not->toBeNull()
        ->and(app(ViewCounter::class)->peek())->toBe([]);
});

it('logs a warning when the console cannot be reached', function () use ($send) {
    Http::fake([TELEMETRY_URL => fn () => throw new ConnectionException('cURL error 28: timed out')]);
    Log::shouldReceive('warning')->once()->withArgs(fn (string $message, array $context) => str_contains($context['error'], 'timed out'));

    $send();

    expect(TelemetrySetting::current()->reported_at)->toBeNull();
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

it('erases the report again if telemetry was turned off while it was in flight', function () use ($send) {
    $installation = TelemetrySetting::current()->installation_id;
    Http::fake([TELEMETRY_URL => function () {
        app(TelemetryService::class)->disable();

        return Http::response(null, 204);
    }]);
    app(ViewCounter::class)->record('index', 5);

    $send();

    Queue::assertPushed(EraseTelemetryJob::class, 2);
    Queue::assertPushed(EraseTelemetryJob::class, fn (EraseTelemetryJob $job) => $job->installation === $installation);

    app(ViewCounter::class)->record('index');

    expect(TelemetrySetting::current()->reported_at)->toBeNull()
        ->and(app(ViewCounter::class)->peek())->toBe(['index' => 1]);
});

it('sends nothing while telemetry is off', function (array $config, ?bool $setting) use ($send) {
    Http::fake();
    config($config);
    TelemetrySetting::current()->update(['enabled' => $setting]);

    $send();

    Http::assertNothingSent();
})->with([
    'admin switch' => [[], false],
    'opt-in default, switch untouched' => [['convoy.telemetry.default' => false], null],
    'environment switch' => [['convoy.telemetry.enabled' => false], true],
    'no URL' => [['convoy.telemetry.url' => ''], true],
]);

it('follows the default until an admin uses the switch', function (bool $default) {
    config(['convoy.telemetry.default' => $default]);

    expect(app(TelemetryService::class)->enabled())->toBe($default);
})->with([true, false]);

it('picks a minute of the UTC day from the installation id, and stays due until a report goes out', function () {
    TelemetrySetting::current()->update(['installation_id' => '00000a00-0000-4000-8000-000000000000']);

    $telemetry = app(TelemetryService::class);
    $at = fn (string $time, string $zone = 'UTC') => CarbonImmutable::parse("2026-10-23 {$time}", $zone);

    // 0xA00 % 1440 = 1120: 18:40 UTC.
    expect($telemetry->minuteOfDay())->toBe(1120)
        ->and($telemetry->isDue($at('18:39:59')))->toBeFalse()
        ->and($telemetry->isDue($at('18:40:00')))->toBeTrue()
        ->and($telemetry->isDue($at('20:40:00', 'Europe/Berlin')))->toBeTrue()
        ->and($telemetry->isDue($at('23:59:00')))->toBeTrue();

    $telemetry->markAttempted($at('18:41:00'));

    expect($telemetry->isDue($at('18:42:00')))->toBeFalse()
        ->and($telemetry->isDue(CarbonImmutable::parse('2026-10-24 18:39:00', 'UTC')))->toBeFalse()
        ->and($telemetry->isDue(CarbonImmutable::parse('2026-10-24 18:40:00', 'UTC')))->toBeTrue();
});

it('schedules the report every minute', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => $event->description === 'telemetry:report');

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *');
});
