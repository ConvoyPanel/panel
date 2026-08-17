<?php

namespace App\Jobs\Middleware;

use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * `WithoutOverlapping`, but the lock frees itself.
 *
 * Laravel releases its lock in a `finally`, which covers a job that throws but not a worker that
 * is killed — and killing the worker is a routine event here. `compose up -d` SIGKILLs the queue
 * container once `stop_grace_period` runs out, and a process that is gone runs no `finally`. The
 * lock's default TTL is 0, which a Redis lock reads as *never expires*, so the abandoned key
 * outlives the upgrade — Valkey runs with `appendonly`, so it outlives every restart after that
 * too — and every later job for that server waits on a lock nobody is left to release.
 *
 * Two minutes is far above any legitimate hold. The lock covers a single *execution*, not the
 * whole operation: these jobs start a Proxmox task and then poll it, releasing themselves between
 * checks, so one pass is a couple of HTTP calls capped at `GUZZLE_TIMEOUT` (15s). It is also short
 * enough that a killed worker heals before anyone notices.
 *
 * The prefix differs from Laravel's deliberately. Locks stranded by an earlier version have no TTL
 * and would still be sitting there when this ships; moving the keyspace orphans them instead of
 * asking every operator to go and flush Redis by hand.
 */
class ExpiringWithoutOverlapping extends WithoutOverlapping
{
    /** Seconds before an abandoned lock releases itself. */
    public const EXPIRES_AFTER = 120;

    /**
     * Seconds a blocked job waits before trying again. Laravel defaults to 0, which re-queues it
     * immediately and spins the worker against the lock for as long as the holder runs.
     */
    public const RELEASE_AFTER = 1;

    /** @var string */
    public $prefix = 'convoy-queue-overlap:';

    public function __construct(string $key)
    {
        parent::__construct($key, self::RELEASE_AFTER, self::EXPIRES_AFTER);
    }
}
