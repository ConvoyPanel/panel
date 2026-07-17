<?php

namespace App\Console\Commands\Node;

use App\Jobs\Node\PollNodeStatusJob;
use App\Models\Node;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Task;

class PollNodeStatusesCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Check whether each node is reachable and record the result.';

    /**
     * @var string
     */
    protected $signature = 'nodes:poll';

    public function handle(): int
    {
        $this->info('Queuing node status polls.');

        Node::query()->each(function (Node $node) {
            (new Task($this->output))->render("Node {$node->fqdn}", function () use ($node) {
                PollNodeStatusJob::dispatch($node->id);

                return true;
            });
        });

        return Command::SUCCESS;
    }
}
