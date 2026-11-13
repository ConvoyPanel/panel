<?php

use Convoy\Jobs\Telemetry\EraseTelemetryJob;
use Convoy\Services\Telemetry\TelemetryService;
use Convoy\Settings\TelemetrySettings;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

$url = 'https://console.test/api/v1/telemetry';
$installation = '6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51';

beforeEach(function () use ($url, $installation) {
    Cache::flush();
    config(['convoy.telemetry.enabled' => true, 'convoy.telemetry.url' => $url]);

    $settings = app(TelemetrySettings::class);
    $settings->enabled = true;
    $settings->installation_id = $installation;
    $settings->save();
});

it('stops reporting, replaces the id and has the console erase the old one', function () use ($url, $installation) {
    $this->artisan('c:telemetry:disable')
        ->expectsOutputToContain('asked to erase')
        ->assertSuccessful();

    $settings = app(TelemetrySettings::class)->refresh();

    expect($settings->enabled)->toBeFalse()
        ->and($settings->installation_id)->toBeString()->not->toBe($installation)->toMatch('/^[0-9a-f-]{36}$/')
        ->and($settings->reported_at)->toBeNull()
        ->and($settings->counting_since)->toBeNull()
        ->and(app(TelemetryService::class)->enabled())->toBeFalse();

    Queue::assertPushed(EraseTelemetryJob::class, fn (EraseTelemetryJob $job) => $job->installation === $installation);

    Http::fake(["{$url}/*" => Http::response(null, 204)]);

    app()->call([new EraseTelemetryJob($installation), 'handle']);

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && $request->url() === "{$url}/{$installation}"
        && $request->hasHeader('User-Agent', 'Convoy/'.config('app.version')));
});

it('sends nothing once turned off', function () use ($url) {
    Http::fake();

    app(TelemetryService::class)->disable();

    runTelemetryReportJob();

    Http::assertNothingSent();
});

it('retries the erase for hours when the console can not be reached', function () use ($url, $installation) {
    Http::fake(["{$url}/*" => Http::response(['message' => 'Server Error'], 500)]);

    $job = new EraseTelemetryJob($installation);

    expect(fn () => app()->call([$job, 'handle']))->toThrow(RequestException::class)
        ->and($job->tries)->toBe(5)
        ->and(array_sum($job->backoff()))->toBeGreaterThan(6 * 3600);
});

it('starts over under a new id when turned back on', function () use ($url, $installation) {
    $this->artisan('c:telemetry:disable')->assertSuccessful();
    $this->freezeSecond();
    $this->artisan('c:telemetry:enable')->expectsOutputToContain('Telemetry is on.')->assertSuccessful();

    $settings = app(TelemetrySettings::class)->refresh();

    expect($settings->enabled)->toBeTrue()
        ->and($settings->installation_id)->not->toBe($installation)
        ->and($settings->counting_since?->toIso8601String())->toBe(now()->toIso8601String());

    Http::fake([$url => Http::response(null, 204), '*' => Http::response(['data' => []])]);

    runTelemetryReportJob();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request['installation'] === $settings->installation_id);
});

it('never contacts the console when CONVOY_TELEMETRY_URL is empty', function () {
    Http::fake();
    config(['convoy.telemetry.url' => '']);

    runTelemetryReportJob();
    $this->artisan('c:telemetry:disable')->expectsOutputToContain('was not contacted')->assertSuccessful();

    Http::assertNothingSent();
    Queue::assertNotPushed(EraseTelemetryJob::class);
});

it('erases a report that went out while it was being turned off', function () use ($installation) {
    $telemetry = app(TelemetryService::class);
    $report = ['installation' => $installation, 'views' => (object) []];

    $telemetry->disable();
    $telemetry->markReported($report, now()->toImmutable());

    Queue::assertPushed(EraseTelemetryJob::class, 2);
    expect(app(TelemetrySettings::class)->refresh()->reported_at)->toBeNull();
});
