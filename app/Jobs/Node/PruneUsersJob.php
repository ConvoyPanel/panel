<?php

namespace Convoy\Jobs\Node;

use Convoy\Models\Node;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Convoy\Services\Nodes\UserPruneService;
use Illuminate\Contracts\Queue\ShouldQueue;

class PruneUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $nodeId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(UserPruneService $service): void
    {
        $node = Node::findOrFail($this->nodeId);

        $service->handle($node);
    }
}
