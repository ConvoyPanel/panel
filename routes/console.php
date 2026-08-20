<?php

use App\Console\Commands\Anchor\PollAnchorLivenessCommand;
use App\Console\Commands\Maintenance\CheckForUpdatesCommand;
use App\Console\Commands\Maintenance\PruneAuditLogsCommand;
use App\Console\Commands\Maintenance\PruneDeploymentsCommand;
use App\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;
use App\Console\Commands\Maintenance\PruneUsersCommand;
use App\Console\Commands\Node\PollNodeStatusesCommand;
use App\Console\Commands\Server\ResetUsagesCommand;
use App\Console\Commands\Server\UpdateRateLimitsCommand;
use App\Console\Commands\Server\UpdateUsagesCommand;
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

// Audit log retention. Not Laravel's generic PruneCommand: events the AuditEvent catalog marks
// as retained forever are exempt, which a `prunable()` scope cannot express per-event.
if (config('audit.prune_days')) {
    Schedule::command(PruneAuditLogsCommand::class)->daily();
}

// Node reachability (see docs/node-status-plan.md). The panel reads only what this
// writes -- checking live state per request costs one PVE call per row, and an
// unreachable node burns the whole connect timeout rather than failing fast.
// `withoutOverlapping` so a fleet that is slow to answer never piles passes up.
Schedule::command(PollNodeStatusesCommand::class)->everyMinute()->withoutOverlapping();

// Anchors normally push heartbeats, so this only picks up the ones that have gone
// quiet -- an Anchor that cannot reach the panel but is reachable *from* it would
// otherwise sit at "Offline" forever. Same reasoning as node polling: the admin
// list must never probe per row, so a background pass keeps it truthful instead.
Schedule::command(PollAnchorLivenessCommand::class)->everyMinute()->withoutOverlapping();

// Whether a newer release of the panel exists. Same rule as the pollers above:
// the admin area reads only what this writes, so nothing an admin loads ever
// waits on GitHub. Hourly is far below the API's rate limit and well inside how
// quickly anyone needs to hear about a release.
Schedule::command(CheckForUpdatesCommand::class)->hourly()->withoutOverlapping();

// Bandwidth quota + rate limiting (see docs/bandwidth-rate-limiting-plan.md).
// Usage must accumulate for quotas to ever trip, so all three run together:
// accumulate usage, reset it on each server's monthly anniversary, and reconcile
// the resulting speed caps / overage penalties onto the VMs' NICs.
Schedule::command(UpdateUsagesCommand::class)->everyFiveMinutes();
Schedule::command(ResetUsagesCommand::class)->dailyAt('00:05');
Schedule::command(UpdateRateLimitsCommand::class)->everyTenMinutes();

// Schedule::command(PruneUsersCommand::class)->daily();
