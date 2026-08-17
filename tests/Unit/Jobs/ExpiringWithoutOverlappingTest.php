<?php

use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Finder\Finder;

/** A stand-in for a queued job: the middleware only ever calls release() on it. */
function overlappingJob(): object
{
    return new class
    {
        public ?int $releasedAfter = null;

        public function release($delay = 0): void
        {
            $this->releasedAfter = $delay;
        }
    };
}

beforeEach(function () {
    // The middleware takes its lock from whichever cache repository the container hands out.
    // Pinning the default to the array store keeps the timing test deterministic — travel()
    // moves Carbon, which the array store honours and a live Redis TTL does not.
    config(['cache.default' => 'array']);

    $this->cache = Cache::store('array');
});

it('gives the lock a finite lifetime', function () {
    $middleware = new ExpiringWithoutOverlapping('7');

    // The bug this class exists for: Laravel's default of 0 means a Redis lock that never
    // expires, so a worker killed mid-job strands the key and wedges that server for good.
    expect($middleware->expiresAfter)->toBe(ExpiringWithoutOverlapping::EXPIRES_AFTER)
        ->and($middleware->expiresAfter)->toBeGreaterThan(0);
});

it('keeps its locks out of the keyspace the unfixed version used', function () {
    // Locks stranded by an earlier release have no TTL and are still sitting in Valkey. A
    // separate prefix orphans them instead of inheriting them.
    expect((new ExpiringWithoutOverlapping('7'))->prefix)->not->toBe('laravel-queue-overlap:');
});

it('releases the lock when the job throws', function () {
    $middleware = new ExpiringWithoutOverlapping('7');
    $job = overlappingJob();

    expect(fn () => $middleware->handle($job, fn () => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class);

    expect($this->cache->lock($middleware->getLockKey($job), 10)->get())->toBeTrue();
});

it('holds a blocked job off instead of spinning on the lock', function () {
    $middleware = new ExpiringWithoutOverlapping('7');
    $job = overlappingJob();

    $this->cache->lock($middleware->getLockKey($job), ExpiringWithoutOverlapping::EXPIRES_AFTER)->get();

    $ran = false;
    $middleware->handle($job, function () use (&$ran) {
        $ran = true;
    });

    // Laravel's default releaseAfter of 0 re-queues immediately, which spins the worker against
    // the lock for as long as the holder runs.
    expect($ran)->toBeFalse()
        ->and($job->releasedAfter)->toBe(ExpiringWithoutOverlapping::RELEASE_AFTER)
        ->and($job->releasedAfter)->toBeGreaterThan(0);
});

it('lets a later job through once an abandoned lock has aged out', function () {
    $middleware = new ExpiringWithoutOverlapping('7');
    $job = overlappingJob();

    // Stand in for the worker SIGKILLed while holding this: the lock is taken and nothing is
    // left running to release it. Before the expiry was set, this wedged the server forever.
    $this->cache->lock($middleware->getLockKey($job), ExpiringWithoutOverlapping::EXPIRES_AFTER)->get();

    $this->travel(ExpiringWithoutOverlapping::EXPIRES_AFTER + 1)->seconds();

    $ran = false;
    $middleware->handle($job, function () use (&$ran) {
        $ran = true;
    });

    expect($ran)->toBeTrue();
});

it('is used by every job that guards against overlap', function () {
    $offenders = [];

    foreach (Finder::create()->files()->in(base_path('app/Jobs'))->name('*.php') as $file) {
        if (str_contains($file->getContents(), 'Illuminate\\Queue\\Middleware\\WithoutOverlapping')
            && ! str_contains($file->getRelativePathname(), 'Middleware')) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    // Laravel's version locks forever. Reaching for it directly reintroduces the wedge.
    expect($offenders)->toBe([]);
});
