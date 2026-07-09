<?php

use App\Console\Commands\Maintenance\PruneDeploymentsCommand;
use App\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;
use App\Console\Commands\Maintenance\PruneUsersCommand;
use App\Console\Commands\Server\ResetUsagesCommand;
use App\Console\Commands\Server\UpdateRateLimitsCommand;
use App\Console\Commands\Server\UpdateUsagesCommand;
use App\Models\ActivityLog;
use App\Models\SessionRecord;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-batches')->daily();
Schedule::command('queue:prune-failed')->daily();

// Capture admin-overview metrics into VictoriaMetrics for dashboard deltas/sparklines. The command
// self-skips when VM is unconfigured, so this is a no-op on installs without it.
if (config('metrics.victoriametrics.url')) {
    Schedule::command('metrics:snapshot')->hourly();
}

// Garbage-collect session metadata rows whose underlying session has aged out (see
// SessionRecord::prunable) so the table stays bounded even for sessions never listed.
Schedule::command(PruneCommand::class, ['--model' => [SessionRecord::class]])->daily();

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
