<?php

namespace App\Console\Commands\Maintenance;

use App\Repositories\Eloquent\BackupRepository;
use Carbon\CarbonImmutable;
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

    public function handle(BackupRepository $repository): void
    {
        $since = $this->option('prune-age') ?? config('backups.prune_age', 360);
        if (! $since || ! is_int($since)) {
            throw new InvalidArgumentException('The "--prune-age" argument must be a value greater than 0.');
        }

        $query = $repository->getBuilder()
            ->whereNull('completed_at')
            ->where('created_at', '<=', CarbonImmutable::now()->subMinutes($since)->toDateTimeString());

        $count = $query->count();
        if (! $count) {
            $this->info('There are no orphaned backups to be marked as failed.');

            return;
        }

        $this->warn("Marking {$count} backups that have not been marked as completed in the last {$since} minutes as failed.");

        $query->update([
            'is_successful' => false,
            'completed_at' => CarbonImmutable::now(),
            'updated_at' => CarbonImmutable::now(),
        ]);
    }
}
