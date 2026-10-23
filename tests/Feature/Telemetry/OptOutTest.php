<?php

use Convoy\Models\Setting;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

$url = 'https://console.test/api/v1/telemetry';
$installation = '6f1c2c8e-9b5a-4c1e-8e0f-2a7d3b9c4e51';

beforeEach(function () use ($url, $installation) {
    config(['convoy.telemetry.enabled' => true, 'convoy.telemetry.url' => $url]);

    Setting::put(TelemetryService::INSTALLATION, $installation);
    Setting::forget(TelemetryService::DISABLED);
});

it('erases the installation on the console and stops reporting when disabled', function () use ($url, $installation) {
    Http::fake(["{$url}/*" => Http::response(null, 204)]);

    $this->artisan('c:telemetry:disable')
        ->expectsOutputToContain('the console has erased')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && $request->url() === "{$url}/{$installation}");

    expect(Setting::get(TelemetryService::INSTALLATION))->toBeNull()
        ->and(app(TelemetryService::class)->enabled())->toBeFalse()
        ->and(app(TelemetryService::class)->reportTime())->toBeNull();

    runTelemetryReportJob();

    Http::assertSentCount(1);
});

it('starts over under a new id when turned back on', function () use ($url, $installation) {
    Http::fake(["{$url}/*" => Http::response(null, 204), $url => Http::response(null, 204), '*' => Http::response(['data' => []])]);

    $this->artisan('c:telemetry:disable')->assertSuccessful();
    $this->artisan('c:telemetry:enable')->expectsOutputToContain('Telemetry is on.')->assertSuccessful();

    runTelemetryReportJob();

    $new = Setting::get(TelemetryService::INSTALLATION);

    expect($new)->toBeString()->not->toBe($installation);
    Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request['installation'] === $new);
});

it('keeps the id and asks again daily when the console cannot erase it yet', function () use ($url, $installation) {
    Http::fakeSequence("{$url}/*")->push(['message' => 'Server Error'], 500)->push(null, 204);

    $this->artisan('c:telemetry:disable')
        ->expectsOutputToContain('could not be reached')
        ->assertFailed();

    expect(Setting::get(TelemetryService::INSTALLATION))->toBe($installation)
        ->and(app(TelemetryService::class)->reportTime())->not->toBeNull();

    runTelemetryReportJob();

    expect(Setting::get(TelemetryService::INSTALLATION))->toBeNull();
    Http::assertSentCount(2);
    Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
});

it('erases the installation on the next run once CONVOY_TELEMETRY_ENABLED is false', function () use ($url, $installation) {
    Http::fake(["{$url}/*" => Http::response(null, 204)]);
    config(['convoy.telemetry.enabled' => false]);

    runTelemetryReportJob();

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && $request->url() === "{$url}/{$installation}");
    expect(Setting::get(TelemetryService::INSTALLATION))->toBeNull();

    runTelemetryReportJob();

    Http::assertSentCount(1);
});

it('never contacts the console when CONVOY_TELEMETRY_URL is empty', function () {
    Http::fake();
    config(['convoy.telemetry.url' => '']);

    runTelemetryReportJob();
    $this->artisan('c:telemetry:disable')->expectsOutputToContain('was not contacted')->assertSuccessful();

    Http::assertNothingSent();
    expect(app(TelemetryService::class)->reportTime())->toBeNull();
});
