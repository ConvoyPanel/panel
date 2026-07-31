<?php

use App\Models\Anchor;
use Illuminate\Support\Facades\Http;

it('probes an enrolled Anchor whose heartbeat has gone stale', function () {
    $anchor = Anchor::factory()->enrolled()->create([
        'public_url' => 'https://agent.example.com',
        'last_seen_at' => now()->subHour(),
        'version' => '0.0.1-stale',
    ]);

    Http::fake(['agent.example.com/api/v1/info' => Http::response([
        'version' => '0.1.0-alpha.1',
        'mode' => 'agent',
        'protocol' => ['min' => Anchor::PROTOCOL_VERSION, 'max' => Anchor::PROTOCOL_VERSION],
        'capabilities' => ['console.qemu.vnc'],
    ])]);

    $this->artisan('anchors:poll')->assertSuccessful();

    expect($anchor->refresh()->version)->toBe('0.1.0-alpha.1')
        ->and($anchor->last_seen_at->isAfter(now()->subMinute()))->toBeTrue();
});

it('leaves Anchors alone while their heartbeat is still fresh', function () {
    // A recent heartbeat already carries everything a probe would return, so
    // spending a request on it every minute would be pure waste.
    Anchor::factory()->enrolled()->create(['public_url' => 'https://agent.example.com']);

    Http::fake();

    $this->artisan('anchors:poll')->assertSuccessful();

    Http::assertNothingSent();
});

it('never probes an Anchor that was never enrolled', function () {
    Anchor::factory()->create(['public_url' => 'https://agent.example.com']);

    Http::fake();

    $this->artisan('anchors:poll')->assertSuccessful();

    Http::assertNothingSent();
});

it('keeps going when one Anchor cannot be reached', function () {
    $unreachable = Anchor::factory()->enrolled()->create([
        'public_url' => 'https://down.example.com',
        'last_seen_at' => now()->subHour(),
    ]);
    $reachable = Anchor::factory()->enrolled()->create([
        'public_url' => 'https://up.example.com',
        'last_seen_at' => now()->subHour(),
    ]);

    Http::fake([
        'down.example.com/*' => Http::response(status: 502),
        'up.example.com/*' => Http::response([
            'version' => '0.1.0-alpha.1',
            'mode' => 'agent',
            'protocol' => ['min' => Anchor::PROTOCOL_VERSION, 'max' => Anchor::PROTOCOL_VERSION],
            'capabilities' => [],
        ]),
    ]);

    $this->artisan('anchors:poll')->assertSuccessful();

    expect($reachable->refresh()->last_seen_at->isAfter(now()->subMinute()))->toBeTrue()
        ->and($unreachable->refresh()->last_seen_at->isBefore(now()->subMinutes(30)))->toBeTrue();
});
