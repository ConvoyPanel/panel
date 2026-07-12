<?php

namespace App\Console\Commands\Server;

use App\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;

class ResetUsagesCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Reset each server\'s bandwidth usage on its own monthly anniversary.';

    /**
     * @var string
     */
    protected $signature = 'servers:reset-usages';

    /**
     * Runs daily and zeroes the bandwidth usage of every server whose reset
     * anchor falls on today. The anchor is `bandwidth_reset_day`, falling back to
     * the server's creation day. Anchors past the current month's length (e.g. 31
     * in February) collapse onto the last day of the month.
     *
     * See docs/bandwidth-rate-limiting-plan.md §6.
     */
    public function handle(): void
    {
        $now = now();
        $today = $now->day;
        $daysInMonth = $now->daysInMonth;

        // COALESCE(bandwidth_reset_day, day-of-created_at): the effective anchor.
        $anchor = 'COALESCE(bandwidth_reset_day, EXTRACT(DAY FROM created_at))';

        $count = Server::query()
            ->where(function (Builder $query) use ($anchor, $today, $daysInMonth) {
                $query->whereRaw("{$anchor} = ?", [$today]);

                // On the last day of the month, also sweep anchors that never
                // occur this month (29–31 in short months).
                if ($today === $daysInMonth) {
                    $query->orWhereRaw("{$anchor} > ?", [$daysInMonth]);
                }
            })
            ->update(['bandwidth_usage' => 0]);

        $this->info("Reset bandwidth usage for {$count} server(s).");
    }
}
