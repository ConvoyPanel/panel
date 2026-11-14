<?php

use Convoy\Services\Telemetry\ViewCounter;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::flush());

it('reads counts without resetting them', function () {
    $views = app(ViewCounter::class);
    $views->record('servers.backups');
    $views->record('servers.backups', 2);
    $views->record('admin.nodes.index');

    expect($views->peek())->toBe(['admin.nodes.index' => 1, 'servers.backups' => 3])
        ->and($views->peek())->toBe(['admin.nodes.index' => 1, 'servers.backups' => 3]);
});

it('takes back only what a report carried, so views during the send are kept', function () {
    $views = app(ViewCounter::class);
    $views->record('index', 5);

    $sent = $views->peek();
    $views->record('index', 2);
    $views->subtract($sent);

    expect($views->peek())->toBe(['index' => 2]);

    $views->subtract($views->peek());

    expect($views->peek())->toBe([]);
});

it('ignores keys the console would refuse', function (string $key) {
    $views = app(ViewCounter::class);
    $views->record($key);

    expect($views->peek())->toBe([]);
})->with(['Servers.Show', 'servers show', 'servers..show', '/servers/:id', '', str_repeat('a', 121)]);

it('stops adding pages at the console\'s limit, but keeps counting the ones it has', function () {
    $views = app(ViewCounter::class);

    foreach (range(1, ViewCounter::MAX_KEYS) as $page) {
        $views->record("page-{$page}");
    }

    $views->record('one-too-many');
    $views->record('page-1');

    $peek = $views->peek();

    expect($peek)->toHaveCount(ViewCounter::MAX_KEYS)
        ->and($peek)->not->toHaveKey('one-too-many')
        ->and($peek['page-1'])->toBe(2);
});

it('forgets everything on clear', function () {
    $views = app(ViewCounter::class);
    $views->record('index', 4);
    $views->clear();

    expect($views->peek())->toBe([]);

    $views->record('index');

    expect($views->peek())->toBe(['index' => 1]);
});
