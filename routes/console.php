<?php

use App\Console\Commands\Maintenance\PruneDeploymentsCommand;
use App\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;
use App\Console\Commands\Maintenance\PruneUsersCommand;
use App\Console\Commands\Server\ResetUsagesCommand;
use App\Console\Commands\Server\UpdateRateLimitsCommand;
use App\Console\Commands\Server\UpdateUsagesCommand;
use App\Models\ActivityLog;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-batches')->daily();
Schedule::command('queue:prune-failed')->daily();

if (config('deployments.stuck_age') || config('deployments.retention_period')) {
    Schedule::command(PruneDeploymentsCommand::class)->hourly();
}

if (config('backups.prune_age')) {
    // Every 30 minutes, run the backup pruning command so that any abandoned backups can be deleted.
    Schedule::command(PruneOrphanedBackupsCommand::class)->everyThirtyMinutes();
}

if (config('activity.prune_days')) {
    Schedule::command(PruneCommand::class, ['--model' => [ActivityLog::class]])->daily();
}

// Schedule::command(ResetUsagesCommand::class)->daily();
// Schedule::command(PruneUsersCommand::class)->daily();
// Schedule::command(UpdateUsagesCommand::class)->everyFiveMinutes();
// Schedule::command(UpdateRateLimitsCommand::class)->everyTenMinutes();
