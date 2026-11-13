<?php

namespace Convoy\Console;

use Carbon\CarbonImmutable;
use Convoy\Models\ActivityLog;
use Illuminate\Console\Scheduling\Schedule;
use Convoy\Console\Commands\Announcements\CheckAnnouncementsCommand;
use Illuminate\Database\Console\PruneCommand;
use Convoy\Services\Telemetry\TelemetryService;
use Convoy\Jobs\Telemetry\SendTelemetryReportJob;
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

        // The announcements feed and the latest release on GitHub (Settings → Updates).
        $schedule->command(CheckAnnouncementsCommand::class)->hourly()->withoutOverlapping();

        // The anonymous daily report (docs/telemetry.md). Checked every minute, but due in only one
        // of them: each install takes its own minute of the UTC day from its random id, which
        // spreads the console's load over 24 hours. Not registered at all when the environment
        // turns telemetry off. The switch is read from the database, so a database that can't be
        // reached or hasn't been migrated skips the report rather than failing the schedule.
        if (config('convoy.telemetry.enabled') && config('convoy.telemetry.url')) {
            $schedule->job(new SendTelemetryReportJob)
                ->everyMinute()
                ->name('telemetry:report')
                ->when(fn () => rescue(
                    fn () => $this->app->make(TelemetryService::class)->isDue(CarbonImmutable::now()),
                    false,
                    false,
                ));
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
