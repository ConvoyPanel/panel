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

// Bandwidth quota + rate limiting (see docs/bandwidth-rate-limiting-plan.md).
// Usage must accumulate for quotas to ever trip, so all three run together:
// accumulate usage, reset it on each server's monthly anniversary, and reconcile
// the resulting speed caps / overage penalties onto the VMs' NICs.
Schedule::command(UpdateUsagesCommand::class)->everyFiveMinutes();
Schedule::command(ResetUsagesCommand::class)->dailyAt('00:05');
Schedule::command(UpdateRateLimitsCommand::class)->everyTenMinutes();

// Schedule::command(PruneUsersCommand::class)->daily();
