<?php

namespace Convoy\Console;

use Carbon\CarbonImmutable;
use Convoy\Jobs\Telemetry\SendTelemetryReportJob;
use Convoy\Models\ActivityLog;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use Convoy\Console\Commands\Server\ResetUsagesCommand;
use Convoy\Console\Commands\Server\UpdateUsagesCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Convoy\Console\Commands\Maintenance\PruneUsersCommand;
use Convoy\Console\Commands\Server\UpdateRateLimitsCommand;
use Convoy\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;

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

        // The anonymous daily report (docs/telemetry.md). Checked every minute, but due only from
        // this panel's own minute of the UTC day until a report has gone out that day, which
        // spreads the console's load over 24 hours. Not registered at all when the environment
        // turns telemetry off.
        if (config('convoy.telemetry.enabled') && filled(config('convoy.telemetry.url'))) {
            $schedule->job(new SendTelemetryReportJob)
                ->everyMinute()
                ->name('telemetry:report')
                ->when(fn (TelemetryService $telemetry) => $telemetry->isDue(CarbonImmutable::now()));
        }
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
