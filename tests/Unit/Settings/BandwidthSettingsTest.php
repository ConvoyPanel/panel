<?php

use App\Settings\BandwidthSettings;

it('exposes the seeded overage defaults', function () {
    $settings = app(BandwidthSettings::class);

    expect($settings->overage_action)->toBe('throttle')
        ->and($settings->overage_rate)->toBe(1_000_000);
});

it('persists and reloads changes', function () {
    $settings = app(BandwidthSettings::class);
    $settings->overage_action = 'disconnect';
    $settings->overage_rate = 5_000_000;
    $settings->save();

    // Fresh resolution should reflect the saved values (not the class defaults).
    $reloaded = app(BandwidthSettings::class)->refresh();

    expect($reloaded->overage_action)->toBe('disconnect')
        ->and($reloaded->overage_rate)->toBe(5_000_000);
});
