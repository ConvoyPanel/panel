<?php

use Illuminate\Support\Carbon;

afterEach(fn () => Carbon::setTestNow());

it('resets only servers whose anchor falls on today', function () {
    Carbon::setTestNow('2026-06-15 00:05:00');

    [, , , $due] = createServerModel();
    $due->update(['bandwidth_reset_day' => 15, 'bandwidth_usage' => 500 * 1048576]);

    [, , , $notDue] = createServerModel();
    $notDue->update(['bandwidth_reset_day' => 16, 'bandwidth_usage' => 500 * 1048576]);

    $this->artisan('servers:reset-usages')->assertSuccessful();

    expect($due->fresh()->bandwidth_usage)->toBe(0)
        ->and($notDue->fresh()->bandwidth_usage)->toBe(500 * 1048576);
});

it('falls back to the creation day when no anchor is stored', function () {
    Carbon::setTestNow('2026-06-15 00:05:00');

    [, , , $server] = createServerModel();
    // No explicit anchor; created_at day is 15 -> due today.
    $server->forceFill([
        'bandwidth_reset_day' => null,
        'created_at' => Carbon::parse('2026-01-15 09:00:00'),
    ])->saveQuietly();
    $server->update(['bandwidth_usage' => 500 * 1048576]);

    $this->artisan('servers:reset-usages')->assertSuccessful();

    expect($server->fresh()->bandwidth_usage)->toBe(0);
});

it('collapses anchors past the month length onto the last day', function () {
    // 2026 is not a leap year: February has 28 days.
    Carbon::setTestNow('2026-02-28 00:05:00');

    [, , , $day31] = createServerModel();
    $day31->update(['bandwidth_reset_day' => 31, 'bandwidth_usage' => 500 * 1048576]);

    [, , , $day10] = createServerModel();
    $day10->update(['bandwidth_reset_day' => 10, 'bandwidth_usage' => 500 * 1048576]);

    $this->artisan('servers:reset-usages')->assertSuccessful();

    expect($day31->fresh()->bandwidth_usage)->toBe(0)
        ->and($day10->fresh()->bandwidth_usage)->toBe(500 * 1048576);
});
