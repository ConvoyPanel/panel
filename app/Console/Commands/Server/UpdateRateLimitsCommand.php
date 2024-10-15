<?php

namespace App\Console\Commands\Server;

use App\Jobs\Node\SyncServerRateLimitsJob;
use App\Models\Node;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Task;

class UpdateRateLimitsCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Sync the network rate limits of all servers.';

    /**
     * @var string
     */
    protected $signature = 'c:servers:sync-rate-limits';

    /**
     * Handle command execution.
     */
    public function handle(): int
    {
        $this->info('Queuing rate limits sync request.');

        $nodes = Node::all();

        $nodes->each(function (Node $node) {
            (new Task($this->output))->render("Node {$node->fqdn}", function () use ($node) {
                SyncServerRateLimitsJob::dispatch($node->id);
            });
        });

        return Command::SUCCESS;
    }
}
