<?php

use Carbon\CarbonImmutable;
use Convoy\Jobs\Telemetry\SendTelemetryReportJob;
use Convoy\Services\Telemetry\TelemetryService;
use Convoy\Settings\TelemetrySettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$url = 'https://console.test/api/v1/telemetry';

beforeEach(function () use ($url) {
    Cache::flush();
    config(['convoy.telemetry.enabled' => true, 'convoy.telemetry.url' => $url]);
});

it('is on with a random installation id from the moment the panel is migrated', function () {
    $rows = DB::table('settings')->where('group', 'telemetry')->pluck('payload', 'name');

    expect($rows->keys()->sort()->values()->all())->toBe(['counting_since', 'enabled', 'installation_id', 'reported_at'])
        ->and(json_decode($rows['enabled']))->toBeTrue()
        ->and(json_decode($rows['installation_id']))->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and(json_decode($rows['reported_at']))->toBeNull()
        ->and(app(TelemetryService::class)->enabled())->toBeTrue();
});

it('sends the report to the console under the stored installation id', function () use ($url) {
    $this->freezeSecond();
    Http::fake([$url => Http::response(null, 204), '*' => Http::response(['data' => []])]);

    runTelemetryReportJob();

    $settings = app(TelemetrySettings::class)->refresh();

    Http::assertSent(fn (Request $request) => $request->url() === $url
        && $request->method() === 'POST'
        && $request->hasHeader('User-Agent', 'Convoy/'.config('app.version'))
        && $request->hasHeader('Accept', 'application/json')
        && $request['schema'] === 1
        && $request['installation'] === $settings->installation_id);

    expect($settings->reported_at?->toIso8601String())->toBe(now()->toIso8601String());
});

it('sends at most one report per UTC day', function () use ($url) {
    Http::fake([$url => Http::response(null, 204), '*' => Http::response(['data' => []])]);

    runTelemetryReportJob();
    runTelemetryReportJob();

    Http::assertSentCount(1);

    $this->travel(1)->days();
    runTelemetryReportJob();

    Http::assertSentCount(2);
});

it('logs a warning and waits for the next day when the console cannot be reached', function () use ($url) {
    Log::spy();
    Http::fake([
        $url => fn () => throw new ConnectionException('cURL error 28: Operation timed out after 10001 milliseconds'),
    ]);

    runTelemetryReportJob();

    Log::shouldHaveReceived('warning')->once();
    expect((new SendTelemetryReportJob)->tries)->toBe(1)
        ->and(app(TelemetrySettings::class)->refresh()->reported_at)->toBeNull();
});

it('logs a warning when the console turns the report down', function () use ($url) {
    Log::spy();
    Http::fake([$url => Http::response(['message' => 'Too Many Attempts.'], 429)]);

    runTelemetryReportJob();

    Log::shouldHaveReceived('warning')->once();
    expect(app(TelemetrySettings::class)->refresh()->reported_at)->toBeNull();
});

it('is due once a day at a minute taken from the installation id', function () {
    $settings = app(TelemetrySettings::class);
    $settings->installation_id = '6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51';
    $settings->save();

    $telemetry = app(TelemetryService::class);

    // 0x6f1c2c8e % 1440 = 1390 minutes past midnight UTC
    expect($telemetry->reportTime())->toBe('23:10')
        ->and($telemetry->isDue(CarbonImmutable::parse('2026-11-13 23:10:00', 'UTC')))->toBeTrue()
        ->and($telemetry->isDue(CarbonImmutable::parse('2026-11-13 23:11:00', 'UTC')))->toBeFalse()
        ->and($telemetry->isDue(CarbonImmutable::parse('2026-11-14 00:10:00', 'Europe/Berlin')))->toBeTrue();

    config(['convoy.telemetry.enabled' => false]);

    expect($telemetry->isDue(CarbonImmutable::parse('2026-11-13 23:10:00', 'UTC')))->toBeFalse();
});

it('is scheduled only while the environment allows it', function (bool $enabled, string $url) {
    config(['convoy.telemetry.enabled' => $enabled, 'convoy.telemetry.url' => $url]);

    Artisan::call('schedule:list');

    $scheduled = str_contains(Artisan::output(), 'telemetry:report');

    expect($scheduled)->toBe($enabled && $url !== '');
})->with([
    'on' => [true, 'https://console.test/api/v1/telemetry'],
    'switched off' => [false, 'https://console.test/api/v1/telemetry'],
    'no url' => [true, ''],
]);

it('prints the report without sending it', function () use ($url) {
    Http::fake(['*' => Http::response(['data' => []])]);

    expect(Artisan::call('c:telemetry:show'))->toBe(0);

    $output = Artisan::output();
    $report = json_decode(substr($output, strpos($output, '{')), true);

    expect($output)->toContain('Telemetry is on.')
        ->and($report['schema'])->toBe(1)
        ->and($report['installation'])->toBe(app(TelemetryService::class)->installationId());

    Http::assertNotSent(fn (Request $request) => str_starts_with($request->url(), $url));
});
