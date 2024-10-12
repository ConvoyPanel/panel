<?php

namespace Convoy\Console\Commands\Server;

use Convoy\Jobs\Node\SyncServerUsagesJob;
use Convoy\Models\Node;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Task;

class UpdateUsagesCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Sync the usages of all the servers.';

    /**
     * @var string
     */
    protected $signature = 'c:servers:sync-usages';

    /**
     * Handle command execution.
     */
    public function handle(): int
    {
        $this->info('Queuing usage sync.');

        $nodes = Node::all();

        $nodes->each(function (Node $node) {
            (new Task($this->output))->render("Node {$node->fqdn}", function () use ($node) {
                SyncServerUsagesJob::dispatch($node->id);
            });
        });

        return Command::SUCCESS;
    }
}
