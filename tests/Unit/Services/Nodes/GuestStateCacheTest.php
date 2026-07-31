<?php

use App\Enums\Server\PowerState;
use App\Models\Node;
use App\Models\Server;
use App\Services\Nodes\GuestStateCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->freezeTime();
    $this->cache = app(GuestStateCache::class);
    $this->node = Node::factory()->create();
    $this->server = Server::factory()->for($this->node)->create(['vmid' => 100]);
});

it('reads a guest state out of the node map', function () {
    $this->cache->put($this->node, [100 => 'running']);

    expect($this->cache->stateFor($this->server))->toBe(PowerState::RUNNING);
});

it('prefers a single-guest observation recorded after the last poll', function () {
    $this->cache->put($this->node, [100 => 'stopped']);

    // What a live read from the detail page saw a moment later -- the poll's
    // map is now describing the past.
    $this->travel(1)->seconds();
    $this->cache->observe($this->server, PowerState::RUNNING);

    expect($this->cache->stateFor($this->server))->toBe(PowerState::RUNNING);
});

it('prefers the node map when it was polled after the last observation', function () {
    // The direction that a "single-guest write always wins" shortcut gets
    // wrong: someone watched this guest running, walked away, and it was
    // stopped outside Convoy. The poll saw the truth second.
    $this->cache->observe($this->server, PowerState::RUNNING);

    $this->travel(1)->seconds();
    $this->cache->put($this->node, [100 => 'stopped']);

    expect($this->cache->stateFor($this->server))->toBe(PowerState::STOPPED);
});

it('leaves the other guests on a node untouched when one is observed', function () {
    $neighbour = Server::factory()->for($this->node)->create(['vmid' => 101]);

    $this->cache->put($this->node, [100 => 'stopped', 101 => 'running']);
    $this->travel(1)->seconds();
    $this->cache->observe($this->server, PowerState::RUNNING);

    // The whole point of writing through rather than invalidating: a power
    // action on one server must not blank its neighbours.
    expect($this->cache->stateFor($neighbour))->toBe(PowerState::RUNNING)
        ->and($this->cache->stateFor($this->server))->toBe(PowerState::RUNNING);
});

it('answers from a single-guest observation when the node has never been polled', function () {
    $this->cache->observe($this->server, PowerState::RUNNING);

    expect($this->cache->stateFor($this->server))->toBe(PowerState::RUNNING);
});

it('expires an observation on the same clock as the poll data', function () {
    // Asserted on the expiry handed to the cache rather than by travelling past
    // it: `ArrayStore` (the test driver) expires entries against `microtime()`,
    // so `$this->travel()` -- which only moves Carbon's clock -- never ages a
    // cache entry out.
    //
    // An observation that outlived the guest map would leave one server reading
    // `running` under a node nobody has heard from in an hour.
    Cache::spy();

    $this->cache->observe($this->server, PowerState::RUNNING);

    Cache::shouldHaveReceived('put')->once()->withArgs(
        fn (string $key, array $value, $expiry) => $key === "server:{$this->server->id}:power-state"
            && $value['state'] === PowerState::RUNNING->value
            && $value['observed_at'] === now()->getTimestampMs()
            && $expiry->equalTo(now()->addMinutes(GuestStateCache::TTL_MINUTES))
    );
});

it('gives a tie to the single-guest observation', function () {
    // Same millisecond: the observation looked at this one guest directly,
    // where the poll answered for the whole node at once.
    $this->cache->put($this->node, [100 => 'stopped']);
    $this->cache->observe($this->server, PowerState::RUNNING);

    expect($this->cache->stateFor($this->server))->toBe(PowerState::RUNNING);
});

it('says unknown rather than stopped for a guest the latest poll did not mention', function () {
    $this->cache->observe($this->server, PowerState::RUNNING);

    // The guest was removed outside Convoy, so the next poll simply omits it.
    // Answering `running` off the superseded observation -- or `stopped` off
    // the absence -- would both invite someone to act on a guest that is gone.
    $this->travel(1)->seconds();
    $this->cache->put($this->node, [999 => 'running']);

    expect($this->cache->stateFor($this->server))->toBeNull();
});

it('still understands a node map written by an older release', function () {
    // Pre-timestamp shape: a bare vmid => status map. Readable for the few
    // minutes it takes the next poll to overwrite it.
    Cache::put(GuestStateCache::key($this->node), [100 => 'running'], now()->addMinutes(5));

    expect($this->cache->stateFor($this->server))->toBe(PowerState::RUNNING)
        ->and($this->cache->for($this->node))->toBe([100 => 'running']);
});

it('outranks an undated legacy map with any timestamped observation', function () {
    Cache::put(GuestStateCache::key($this->node), [100 => 'stopped'], now()->addMinutes(5));
    $this->cache->observe($this->server, PowerState::RUNNING);

    expect($this->cache->stateFor($this->server))->toBe(PowerState::RUNNING);
});
