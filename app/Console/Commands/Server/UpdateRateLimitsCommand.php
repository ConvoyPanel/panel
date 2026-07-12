<?php

namespace App\Console\Commands\Server;

use App\Jobs\Server\SyncServerRateLimitJob;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Task;
use Illuminate\Support\Facades\Bus;

class UpdateRateLimitsCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Sync the network rate limits of all servers.';

    /**
     * @var string
     */
    protected $signature = 'servers:sync-rate-limits';

    /**
     * Handle command execution.
     */
    public function handle(): int
    {
        $this->info('Queuing rate limit sync.');

        Node::all()->each(function (Node $node) {
            (new Task($this->output))->render("Node {$node->fqdn}", function () use ($node) {
                $jobs = $node->servers
                    ->map(fn (Server $server) => new SyncServerRateLimitJob($server))
                    ->all();

                if ($jobs === []) {
                    return true;
                }

                // One batch per node: servers sync concurrently, failures are
                // isolated per server, and the batch stays observable in Horizon.
                Bus::batch($jobs)
                    ->name("Sync rate limits for node #{$node->id}")
                    ->allowFailures()
                    ->dispatch();

                return true;
            });
        });

        return Command::SUCCESS;
    }
}
