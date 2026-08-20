<?php

namespace App\Console\Commands\Maintenance;

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use InvalidArgumentException;

class PruneAuditLogsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'maintenance:prune-audit-logs {--prune-days=}';

    /**
     * @var string
     */
    protected $description = 'Deletes audit log entries older than "n" days, except events marked as retained forever.';

    public function handle(): void
    {
        $days = $this->option('prune-days') ?? config('audit.prune_days');

        if (! $days || ! is_numeric($days) || $days <= 0) {
            throw new InvalidArgumentException('The "--prune-days" option must be a value greater than 0.');
        }

        $days = (int) $days;
        $threshold = now()->subDays($days);
        $chunk = max(1, (int) config('audit.prune_chunk', 1000));

        // Security events (authentication, credential and token lifecycle) are exempt — they are
        // the reason the log exists and are far too low-volume to be worth reclaiming.
        $retained = array_map(fn (AuditEvent $event) => $event->value, AuditEvent::retainedForever());

        $deleted = 0;

        // Deleted in chunks so a long-neglected install does not issue one enormous statement, and
        // by explicit id list because Postgres has no DELETE ... LIMIT.
        do {
            $ids = AuditLog::query()
                ->where('created_at', '<=', $threshold)
                ->whereNotIn('event', $retained)
                ->limit($chunk)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += AuditLog::query()->whereIn('id', $ids)->delete();
        } while ($ids->count() === $chunk);

        if ($deleted === 0) {
            $this->info('There are no audit log entries old enough to prune.');

            return;
        }

        $this->info("Pruned {$deleted} audit log entries older than {$days} days.");
    }
}
