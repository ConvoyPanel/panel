<?php

use Convoy\Jobs\Telemetry\SendTelemetryReportJob;
use Convoy\Models\Setting;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$url = 'https://console.test/api/v1/telemetry';

beforeEach(function () use ($url) {
    config(['convoy.telemetry.enabled' => true, 'convoy.telemetry.url' => $url]);

    Setting::forget(TelemetryService::INSTALLATION);
    Setting::forget(TelemetryService::DISABLED);
});

it('sends the report to the console under a stored installation id', function () use ($url) {
    Http::fake([$url => Http::response(null, 204), '*' => Http::response(['data' => []])]);

    runTelemetryReportJob();

    $installation = Setting::get(TelemetryService::INSTALLATION);

    expect($installation)->toBeString()->toMatch('/^[0-9a-f-]{36}$/');

    Http::assertSent(fn (Request $request) => $request->url() === $url
        && $request->method() === 'POST'
        && $request->hasHeader('User-Agent', 'Convoy/'.config('app.version'))
        && $request->hasHeader('Accept', 'application/json')
        && $request['schema'] === 1
        && $request['installation'] === $installation);

    runTelemetryReportJob();

    expect(Setting::get(TelemetryService::INSTALLATION))->toBe($installation);
    Http::assertSentCount(2);
});

it('logs a warning and waits for the next day when the console cannot be reached', function () use ($url) {
    Log::spy();
    Http::fake([
        $url => fn () => throw new ConnectionException('cURL error 28: Operation timed out after 10001 milliseconds'),
    ]);

    runTelemetryReportJob();

    Log::shouldHaveReceived('warning')->once();
    expect((new SendTelemetryReportJob)->tries)->toBe(1);
});

it('logs a warning when the console turns the report down', function () use ($url) {
    Log::spy();
    Http::fake([$url => Http::response(['message' => 'Too Many Attempts.'], 429)]);

    runTelemetryReportJob();

    Log::shouldHaveReceived('warning')->once();
});

it('runs once a day at a minute taken from the installation id', function () {
    Setting::put(TelemetryService::INSTALLATION, '6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51');

    // 0x6f1c2c8e % 1440 = 1390 minutes past midnight
    expect(app(TelemetryService::class)->reportTime())->toBe('23:10');

    Setting::put(TelemetryService::INSTALLATION, '0b8e5c1a-2f3d-4e6a-9b7c-1d2e3f4a5b6c');

    expect(app(TelemetryService::class)->reportTime())->not->toBe('23:10');
});

it('prints the report without sending it', function () use ($url) {
    Http::fake(['*' => Http::response(['data' => []])]);

    expect(Artisan::call('c:telemetry:show'))->toBe(0);

    $output = Artisan::output();
    $report = json_decode(substr($output, strpos($output, '{')), true);

    expect($output)->toContain('Telemetry is on.')
        ->and($report['schema'])->toBe(1)
        ->and($report['installation'])->toBe(Setting::get(TelemetryService::INSTALLATION));

    Http::assertNotSent(fn (Request $request) => str_starts_with($request->url(), $url));
});

it('does not make an installation id to print the report while telemetry is off', function () {
    Http::fake(['*' => Http::response(['data' => []])]);
    config(['convoy.telemetry.enabled' => false]);

    $this->artisan('c:telemetry:show')
        ->expectsOutputToContain('Telemetry is off')
        ->assertSuccessful();

    expect(Setting::get(TelemetryService::INSTALLATION))->toBeNull();
});
