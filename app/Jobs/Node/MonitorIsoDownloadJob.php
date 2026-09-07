<?php

namespace App\Jobs\Node;

use App\Models\ISO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Retired. Kept for one release so queued downloads do not strand.
 *
 * Adding an ISO used to start a download onto one node's storage, and this
 * watched that task to decide whether the row was usable. Nothing downloads at
 * add time now: a library entry is complete when it exists, and the transfer
 * happens on the node that first mounts it.
 *
 * A payload queued by the previous release names a row whose `is_successful`
 * column no longer exists, so there is nothing left to record. It logs and
 * returns rather than throwing: unlike a stranded install, nobody is waiting on
 * this, and failing it loudly would only fill the failed-jobs table.
 *
 * Keeps the old `Iso` casing on purpose. The class name is the wire format of
 * every payload already on the queue, so renaming it to match the rest of the
 * codebase would strand them with `Class not found` -- the precise failure this
 * shim exists to prevent. It goes away with the class.
 *
 * Delete in the release after next, once no queue can still hold one.
 */
class MonitorIsoDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(protected int $isoId, protected string $upid) {}

    public function handle(): void
    {
        Log::info('Discarding an ISO download monitor queued before the library moved into the panel.', [
            'iso' => $this->isoId,
            'exists' => ISO::whereKey($this->isoId)->exists(),
        ]);
    }
}
