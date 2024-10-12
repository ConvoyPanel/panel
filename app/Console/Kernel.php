<?php

namespace App\Console;

use App\Models\ActivityLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use App\Console\Commands\Server\ResetUsagesCommand;
use App\Console\Commands\Server\UpdateUsagesCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\Maintenance\PruneUsersCommand;
use App\Console\Commands\Server\UpdateRateLimitsCommand;
use App\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('queue:prune-batches')->daily();

        if (config('backups.prune_age')) {
            // Every 30 minutes, run the backup pruning command so that any abandoned backups can be deleted.
            $schedule->command(PruneOrphanedBackupsCommand::class)->everyThirtyMinutes();
        }

        if (config('activity.prune_days')) {
            $schedule->command(PruneCommand::class, ['--model' => [ActivityLog::class]])->daily();
        }

        $schedule->command(ResetUsagesCommand::class)->daily();
        $schedule->command(PruneUsersCommand::class)->daily();
        $schedule->command(UpdateUsagesCommand::class)->everyFiveMinutes();
        $schedule->command(UpdateRateLimitsCommand::class)->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        //require base_path('routes/console.php');
    }
}
