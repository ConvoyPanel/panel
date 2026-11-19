<?php

namespace App\Jobs\Middleware;

use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * `WithoutOverlapping`, but the lock frees itself.
 *
 * Laravel releases its lock in a `finally`, which covers a job that throws but not a worker that
 * is killed — and killing the worker is a routine event here. `compose up -d` SIGKILLs the queue
 * container once `stop_grace_period` runs out, and the worker SIGKILLs itself when a job runs past
 * its `$timeout`. A process that is gone runs no `finally`. The lock's default TTL is 0, which a
 * Redis lock reads as *never expires*, so the abandoned key outlives the upgrade — Valkey runs with
 * `appendonly`, so it outlives every restart after that too — and every later job for that server
 * waits on a lock nobody is left to release.
 *
 * The lock lasts the job's own timeout plus a margin. The worker kills a job once its timeout is
 * up, so the lock can't expire under a job that is still running, and a killed job's lock frees
 * itself shortly after. The margin covers the kill landing late: PHP runs the signal handler only
 * once a blocking Proxmox request returns, which can take `GUZZLE_TIMEOUT` plus the connect timeout.
 *
 * The prefix differs from Laravel's deliberately. Locks stranded by an earlier version have no TTL
 * and would still be sitting there when this ships; moving the keyspace orphans them instead of
 * asking every operator to go and flush Redis by hand.
 */
class ExpiringWithoutOverlapping extends WithoutOverlapping
{
    /** Seconds the lock outlives the job's timeout. */
    public const EXPIRY_MARGIN = 30;

    /**
     * Seconds a blocked job waits before trying again. Laravel defaults to 0, which re-queues it
     * immediately and spins the worker against the lock for as long as the holder runs.
     */
    public const RELEASE_AFTER = 1;

    /** @var string */
    public $prefix = 'convoy-queue-overlap:';

    public function __construct(string $key, int $jobTimeout)
    {
        parent::__construct($key, self::RELEASE_AFTER, $jobTimeout + self::EXPIRY_MARGIN);
    }
}
