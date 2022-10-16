<?php

namespace Convoy\Console;

use Convoy\Console\Commands\Maintenance\RefreshActivityRunnersCommand;
use Convoy\Console\Commands\Server\ResetUsagesCommand;
use Convoy\Console\Commands\Server\UpdateRateLimitsCommand;
use Convoy\Console\Commands\Server\UpdateUsagesCommand;
use Convoy\Models\ActivityLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(RefreshActivityRunnersCommand::class)->everyMinute()->withoutOverlapping();
        $schedule->command(UpdateUsagesCommand::class)->everyFiveMinutes()->withoutOverlapping();
        $schedule->command(UpdateRateLimitsCommand::class)->everyTenMinutes()->withoutOverlapping();
        $schedule->command(ResetUsagesCommand::class)->daily();

        if (config('activity.prune_days')) {
            $schedule->command(PruneCommand::class, ['--model' => [ActivityLog::class]])->daily();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
