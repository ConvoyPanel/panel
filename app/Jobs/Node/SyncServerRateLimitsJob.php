<?php

namespace Convoy\Jobs\Node;

use Convoy\Models\Node;
use Convoy\Services\Nodes\ServerRateLimitsSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncServerRateLimitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(protected int $nodeId)
    {
    }

    public function handle(ServerRateLimitsSyncService $service): void
    {
        $node = Node::findOrFail($this->nodeId);

        $service->handle($node);
    }
}
