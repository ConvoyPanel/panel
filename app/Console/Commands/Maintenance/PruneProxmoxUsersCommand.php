<?php

namespace Convoy\Console\Commands\Maintenance;

use Convoy\Jobs\Node\PruneProxmoxUsersJob;
use Convoy\Models\Node;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Task;

class PruneProxmoxUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c:maintenance:prune-proxmox-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prunes expired Proxmox users (for temporary terminal access) from all nodes.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Queuing Proxmox user prune request.');

        $nodes = Node::all();

        $nodes->each(function (Node $node) {
            (new Task($this->output))->render("Node {$node->fqdn}", function () use ($node) {
                PruneProxmoxUsersJob::dispatch($node->id);
            });
        });

        return Command::SUCCESS;
    }
}
