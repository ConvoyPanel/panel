<?php

use Convoy\Services\Admin\VersionUpdateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

it('uses app version as the current version and detects outdated releases', function () {
    config()->set('app.version', '1.0.0');

    Http::fake([
        'https://api.github.com/repos/ConvoyPanel/panel/releases/latest' => Http::response([
            'tag_name' => 'v1.1.0',
            'html_url' => 'https://github.com/ConvoyPanel/panel/releases/tag/v1.1.0',
        ]),
    ]);

    $status = app(VersionUpdateService::class)->status();

    expect($status['status'])->toBe('outdated')
        ->and($status['current_version'])->toBe('1.0.0')
        ->and($status['latest_version'])->toBe('v1.1.0')
        ->and($status['is_outdated'])->toBeTrue();
});

it('normalizes a leading v before comparing versions', function () {
    config()->set('app.version', 'v1.1.0');

    Http::fake([
        'https://api.github.com/repos/ConvoyPanel/panel/releases/latest' => Http::response([
            'tag_name' => 'v1.1.0',
            'html_url' => 'https://github.com/ConvoyPanel/panel/releases/tag/v1.1.0',
        ]),
    ]);

    $status = app(VersionUpdateService::class)->status();

    expect($status['status'])->toBe('current')
        ->and($status['is_outdated'])->toBeFalse();
});

it('reports when the current version is ahead of the latest release', function () {
    config()->set('app.version', '1.2.0');

    Http::fake([
        'https://api.github.com/repos/ConvoyPanel/panel/releases/latest' => Http::response([
            'tag_name' => 'v1.1.0',
            'html_url' => 'https://github.com/ConvoyPanel/panel/releases/tag/v1.1.0',
        ]),
    ]);

    $status = app(VersionUpdateService::class)->status();

    expect($status['status'])->toBe('ahead')
        ->and($status['is_outdated'])->toBeFalse();
});

it('does not compare canary builds or fetch the latest release', function () {
    config()->set('app.version', 'canary');

    Http::fake();

    $status = app(VersionUpdateService::class)->status();

    expect($status['status'])->toBe('canary')
        ->and($status['is_outdated'])->toBeFalse()
        ->and($status['latest_version'])->toBeNull();

    Http::assertNothingSent();
});

it('returns unavailable when the latest release cannot be fetched', function () {
    config()->set('app.version', '1.1.0');

    Http::fake([
        'https://api.github.com/repos/ConvoyPanel/panel/releases/latest' => Http::response(null, 500),
    ]);

    $status = app(VersionUpdateService::class)->status();

    expect($status['status'])->toBe('unavailable')
        ->and($status['is_outdated'])->toBeFalse();
});
