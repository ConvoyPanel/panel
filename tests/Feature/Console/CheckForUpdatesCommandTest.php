<?php

use App\Enums\UpdateStatus;
use App\Services\Admin\UpdateCheckService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

/** @param array<string, mixed> $overrides */
function fakeRelease(array $overrides = []): void
{
    Http::fake(['api.github.com/*' => Http::response([
        'tag_name' => 'v4.7.0',
        'html_url' => 'https://github.com/ConvoyPanel/panel/releases/tag/v4.7.0',
        'published_at' => '2026-08-01T12:00:00Z',
        ...$overrides,
    ])]);
}

it('reports an update when the latest release is newer than the running version', function () {
    config()->set('app.version', '4.6.1');
    fakeRelease();

    $this->artisan('updates:check')->assertSuccessful();

    $status = app(UpdateCheckService::class)->status();

    expect($status->status)->toBe(UpdateStatus::UPDATE_AVAILABLE)
        ->and($status->updateAvailable)->toBeTrue()
        // The `v` prefix is a tagging convention, not part of the version the
        // panel embeds — both sides are compared without it.
        ->and($status->latestVersion)->toBe('4.7.0')
        ->and($status->currentVersion)->toBe('4.6.1')
        ->and($status->releaseUrl)->toBe('https://github.com/ConvoyPanel/panel/releases/tag/v4.7.0');
});

it('reports up to date when the running version matches the latest release', function () {
    config()->set('app.version', '4.7.0');
    fakeRelease();

    $this->artisan('updates:check')->assertSuccessful();

    $status = app(UpdateCheckService::class)->status();

    expect($status->status)->toBe(UpdateStatus::UP_TO_DATE)
        ->and($status->updateAvailable)->toBeFalse();
});

it('offers the stable release to a panel running a release candidate', function () {
    config()->set('app.version', '4.7.0-rc.1');
    fakeRelease();

    $status = app(UpdateCheckService::class)->check();

    expect($status->status)->toBe(UpdateStatus::UPDATE_AVAILABLE);
});

it('cannot compare a source checkout, but still records the latest release', function () {
    // `canary` is what a git tree reports; only the release workflow rewrites it.
    config()->set('app.version', 'canary');
    fakeRelease();

    $this->artisan('updates:check')->assertSuccessful();

    $status = app(UpdateCheckService::class)->status();

    expect($status->status)->toBe(UpdateStatus::UNKNOWN)
        ->and($status->updateAvailable)->toBeFalse()
        ->and($status->latestVersion)->toBe('4.7.0');
});

it('keeps the last known release when a check fails', function () {
    config()->set('app.version', '4.6.1');

    // A sequence, not two `Http::fake()` calls: stubs accumulate and the first
    // match wins, so a second fake for the same URL would never be reached.
    Http::fakeSequence('api.github.com/*')
        ->push([
            'tag_name' => 'v4.7.0',
            'html_url' => 'https://github.com/ConvoyPanel/panel/releases/tag/v4.7.0',
            'published_at' => '2026-08-01T12:00:00Z',
        ])
        ->pushStatus(503);

    $this->artisan('updates:check')->assertSuccessful();
    $this->artisan('updates:check')->assertFailed();

    // A GitHub outage must not make an out-of-date panel look current.
    $status = app(UpdateCheckService::class)->status();

    expect($status->status)->toBe(UpdateStatus::UPDATE_AVAILABLE)
        ->and($status->latestVersion)->toBe('4.7.0');
});

it('reports unknown before any check has completed', function () {
    config()->set('app.version', '4.6.1');

    $status = app(UpdateCheckService::class)->status();

    expect($status->status)->toBe(UpdateStatus::UNKNOWN)
        ->and($status->latestVersion)->toBeNull()
        ->and($status->checkedAt)->toBeNull();
});

it('names the repository it watches', function () {
    $status = app(UpdateCheckService::class)->status();

    expect($status->repository)->toBe(config('convoy.updates.repository'));
});

it('checks the repository named in configuration', function () {
    config()->set('convoy.updates.repository', 'ExampleFork/panel');
    fakeRelease();

    $this->artisan('updates:check')->assertSuccessful();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.github.com/repos/ExampleFork/panel/releases/latest');
});
