<?php

namespace App\Console\Commands\Server;

use App\Models\Server;
use Illuminate\Console\Command;

class ResetUsagesCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Reset the bandwidth usage of all servers on the first of each month.';

    /**
     * @var string
     */
    protected $signature = 'c:server:reset-usages';

    /**
     * Handle command execution.
     */
    public function handle(): void
    {
        if (date('d') === '01') {
            Server::query()->update([
                'bandwidth_usage' => 0,
            ]);
        }
    }
}
