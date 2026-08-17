<?php

use App\Jobs\Server\CloneVmJob;
use App\Support\Jobs\QueuedJobSignatures;

it('has a snapshot that matches the queued jobs in the tree', function () {
    // Convoy's long Proxmox operations re-enqueue themselves until the remote task finishes, so
    // an upgrade always executes payloads that the previous release serialised — there is no
    // moment when the queue is reliably empty to drain it at. A constructor that changed without
    // anyone noticing is how those payloads get stranded.
    //
    // If this failed because you meant to change a job, run:
    //
    //   php artisan maintenance:refresh-job-signatures
    //
    // then read the diff. A parameter that was removed, renamed, reordered or retyped needs the
    // old class kept around for a release; a newly *promoted* parameter needs a default in the
    // class body, because unserialize() skips the constructor and leaves it uninitialized.
    // See App\Support\Jobs\QueuedJobSignatures.
    expect(QueuedJobSignatures::current())->toBe(QueuedJobSignatures::recorded());
});

it('records every queued job', function () {
    // Guards the discovery itself: a filter that silently stopped matching would make the
    // snapshot pass by covering nothing.
    expect(QueuedJobSignatures::recorded())->not->toBeEmpty()
        ->and(array_keys(QueuedJobSignatures::recorded()))
        ->toContain(CloneVmJob::class);
});
