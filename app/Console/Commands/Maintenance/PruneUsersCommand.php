<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Node;
use Illuminate\Console\Command;
use App\Jobs\Node\PruneUsersJob;
use Illuminate\Console\View\Components\Task;

class PruneUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c:maintenance:prune-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prunes all users on all nodes that have expired.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Queuing user prune request.');

        $nodes = Node::all();

        $nodes->each(function (Node $node) {
            (new Task($this->output))->render("Node {$node->fqdn}", function () use ($node) {
                PruneUsersJob::dispatch($node->id);
            });
        });

        return Command::SUCCESS;
    }
}
