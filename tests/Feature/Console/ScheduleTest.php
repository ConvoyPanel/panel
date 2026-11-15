<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

it('lets a scheduled command\'s overlap lock expire within minutes', function () {
    $locked = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event) => $event->withoutOverlapping);

    // Laravel's default is a day. A scheduler killed mid-run strands the lock, and an
    // every-minute poller then skips every run until it expires.
    expect($locked)->not->toBeEmpty();

    $locked->each(fn (Event $event) => expect($event->expiresAt)->toBeLessThanOrEqual(5));
});
