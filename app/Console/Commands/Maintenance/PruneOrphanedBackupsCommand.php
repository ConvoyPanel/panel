<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Backup;
use Illuminate\Console\Command;
use InvalidArgumentException;

class PruneOrphanedBackupsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'c:maintenance:prune-backups {--prune-age=}';

    /**
     * @var string
     */
    protected $description = 'Marks all backups that have not completed in the last "n" minutes as being failed.';

    public function handle(): void
    {
        $since = $this->option('prune-age') ?? config('backups.prune_age', 360);

        if (! $since || ! is_numeric($since)) {
            throw new InvalidArgumentException('The "--prune-age" argument must be a value greater than 0.');
        }

        $since = (int) $since;
        $threshold = now()->subMinutes($since);

        $query = Backup::query()
            ->whereNull('completed_at')
            ->where('created_at', '<=', $threshold);

        $count = $query->count();

        if (! $count) {
            $this->info('There are no orphaned backups to be marked as failed.');

            return;
        }

        $this->warn("Marking {$count} backups that have not been marked as completed in the last {$since} minutes as failed.");

        $query->update([
            'is_successful' => false,
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
