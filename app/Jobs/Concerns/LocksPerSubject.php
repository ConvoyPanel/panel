<?php

namespace Convoy\Jobs\Concerns;

use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Keeps two jobs of the same class from running on the same server (or backup,
 * or ISO) at once, with a lock that can't outlive a killed job.
 *
 * WithoutOverlapping's lock never expires by default, and it's only released
 * when the job finishes. A job the worker kills for running past its timeout
 * never gets there, so the lock stayed forever and every later job of that
 * class for that subject failed straight away -- a slow Proxmox call during a
 * force-stop could break reinstalling and deleting that server until the lock
 * was cleared from Redis by hand.
 *
 * The lock now expires shortly after the job's own timeout, and a job that finds
 * it held waits a few seconds and tries again instead of giving up at once.
 * Laravel already scopes the key by the job's class, so the subject's ID is key
 * enough.
 */
trait LocksPerSubject
{
    protected function lockPerSubject(int|string $subjectId): WithoutOverlapping
    {
        return (new WithoutOverlapping($subjectId))
            ->expireAfter($this->timeout + 30)
            ->releaseAfter(5);
    }
}
