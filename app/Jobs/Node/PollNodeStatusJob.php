<?php

namespace App\Jobs\Node;

use App\Models\Node;
use App\Services\Nodes\NodeStatusPollService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * One job per node so an unreachable host's timeout never serialises behind a
 * healthy one.
 *
 * `tries = 1`: a failed check is the answer, not an error to retry. Retrying
 * would only rewrite the same `unreachable` row while holding a worker for
 * another timeout, and slice 3's debounce (consecutive_failures) is what
 * absorbs a flap.
 */
class PollNodeStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(protected int $nodeId) {}

    public function handle(NodeStatusPollService $service): void
    {
        $node = Node::find($this->nodeId);

        // The node may have been deleted between the poll being queued and run.
        if (! $node) {
            return;
        }

        $service->handle($node);
    }
}
