<?php

use Convoy\Services\Announcements\AnnouncementService;
use Convoy\Services\Announcements\VersionRanges;
use Illuminate\Support\Facades\Cache;
use Tests\Support\FakeAnnouncementFeed;

it('matches from inclusive and before exclusive', function (?array $ranges, string $version, bool $matches) {
    expect(VersionRanges::matches($ranges, $version))->toBe($matches);
})->with([
    'every version' => [null, '5.0.0', true],
    'at from' => [[['from' => '5.0.0']], '5.0.0', true],
    'below from' => [[['from' => '5.0.0']], '4.7.0', false],
    'below before' => [[['before' => '5.0.1']], '5.0.0', true],
    'at before' => [[['before' => '5.0.1']], '5.0.1', false],
    'inside both' => [[['from' => '8.3.0', 'before' => '8.3.36']], '8.3.12', true],
    'past both' => [[['from' => '8.3.0', 'before' => '8.3.36']], '8.4.1', false],
    'any of a list' => [[['before' => '8.2.35'], ['from' => '8.4.0', 'before' => '8.4.27']], '8.4.26', true],
    'none of a list' => [[['before' => '8.2.35'], ['from' => '8.4.0', 'before' => '8.4.27']], '8.4.27', false],
    'an empty list' => [[], '5.0.0', false],
    'a range with no bounds' => [[[]], '5.0.0', true],
    'a leading v' => [[['from' => 'v5.0.0', 'before' => 'v5.1.0']], 'v5.0.3', true],
]);

it('orders release candidates below their release', function (?array $ranges, string $version, bool $matches) {
    expect(VersionRanges::matches($ranges, $version))->toBe($matches);
})->with([
    'an rc before the release' => [[['before' => '4.7.0']], '4.7.0-rc.3', true],
    'the release itself' => [[['before' => '4.7.0']], '4.7.0', false],
    'an rc from the first rc' => [[['from' => '5.0.0-rc.1']], '5.0.0-rc.2', true],
    'the release from an rc' => [[['from' => '5.0.0-rc.1']], '5.0.0', true],
    'an earlier rc' => [[['from' => '5.0.0-rc.2']], '5.0.0-rc.1', false],
    'rc.2 below rc.10' => [[['before' => '5.0.0-rc.10']], '5.0.0-rc.2', true],
]);

it('never matches a canary build against a panel range, but does against none', function () {
    expect(VersionRanges::matches([['before' => '99.0.0']], 'canary'))->toBeFalse()
        ->and(VersionRanges::matches([[]], 'canary'))->toBeFalse()
        ->and(VersionRanges::matches(null, 'canary'))->toBeTrue()
        ->and(VersionRanges::matches([['from' => '1.0.0']], 'my-fork'))->toBeFalse();
});

it('applies an announcement when the panel and PHP both match', function (string $panel, ?array $panelRanges, ?array $phpRanges, bool $applies) {
    Cache::flush();
    config(['app.version' => $panel]);
    FakeAnnouncementFeed::serve([FakeAnnouncementFeed::entry([
        'affects' => ['panel' => $panelRanges, 'php' => $phpRanges],
    ])]);
    app(\Convoy\Services\Announcements\AnnouncementFeed::class)->refresh();

    $announcements = app(AnnouncementService::class);
    $php = $announcements->phpVersion();

    expect($announcements->applies($announcements->all()[0]))->toBe($applies);
})->with([
    'every panel, every PHP' => ['5.0.0', null, null, true],
    'this panel, every PHP' => ['5.0.0', [['before' => '5.0.1']], null, true],
    'a fixed panel' => ['5.0.1', [['before' => '5.0.1']], null, false],
    'an rc of the fixed release' => ['5.0.1-rc.2', [['before' => '5.0.1']], null, true],
    'canary and a panel range' => ['canary', [['before' => '5.0.1']], null, false],
    'canary and a PHP range that matches' => ['canary', null, [['from' => '8.0.0']], true],
    'canary and a PHP range that does not' => ['canary', null, [['before' => '8.0.0']], false],
    'this panel but not this PHP' => ['5.0.0', [['before' => '5.0.1']], [['before' => '7.0.0']], false],
]);

it('says why an announcement does or doesn\'t apply', function () {
    Cache::flush();
    config(['app.version' => 'canary']);
    FakeAnnouncementFeed::serve([
        FakeAnnouncementFeed::entry(['id' => 'panel-only', 'affects' => ['panel' => [['before' => '5.0.1']], 'php' => null]]),
        FakeAnnouncementFeed::entry(['id' => 'php-only', 'affects' => ['panel' => null, 'php' => [['from' => '8.0.0']]]]),
    ]);
    app(\Convoy\Services\Announcements\AnnouncementFeed::class)->refresh();

    $announcements = app(AnnouncementService::class);
    $php = $announcements->phpVersion();

    expect($announcements->reasons($announcements->find('panel-only')))->toBe([[
        'text' => 'It is for Convoy before 5.0.1. This panel reports "canary" (built from source), which never matches a Convoy version range.',
        'matches' => false,
    ]])->and($announcements->reasons($announcements->find('php-only')))->toBe([
        ['text' => 'It is for every Convoy version.', 'matches' => true],
        ['text' => "This panel runs PHP {$php}, which is affected (8.0.0 and later).", 'matches' => true],
    ]);
});
