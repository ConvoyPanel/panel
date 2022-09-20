<?php

namespace Convoy\Console\Commands\Maintenance;

use Convoy\Facades\LogRunner;
use Convoy\Models\ActivityLog;
use Illuminate\Console\Command;

class RefreshActivityRunnersCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Update running tasks by polling Proxmox.';

    /**
     * @var string
     */
    protected $signature = 'c:maintenance:refresh-activity-runners';

    /**
     * Handle command execution.
     */
    public function handle()
    {
        $runners = ActivityLog::where('status', 'running')->whereIn('event', array_keys(ActivityLog::$eventTypes))->get();

        foreach ($runners as $runner)
        {
            LogRunner::setNode($runner->serverSubject()->node)->setActivity($runner)->refresh();
        }
    }
}