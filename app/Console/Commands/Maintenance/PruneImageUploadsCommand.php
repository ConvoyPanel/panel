<?php

namespace App\Console\Commands\Maintenance;

use App\Services\Images\ChunkedUploadService;
use Illuminate\Console\Command;

/**
 * Reclaims the disk of uploads nobody came back to finish.
 *
 * The expensive kind of leftover: an abandoned Windows image is several
 * gigabytes that nothing references and no screen lists, so without this it
 * would sit on the panel's disk until someone noticed it by running out.
 */
class PruneImageUploadsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'maintenance:prune-image-uploads {--hours=}';

    /**
     * @var string
     */
    protected $description = 'Deletes partially uploaded disk images that have not been touched recently.';

    public function handle(ChunkedUploadService $uploads): void
    {
        $hours = (int) ($this->option('hours') ?? config('convoy.artifacts.upload_ttl_hours', 24));

        $pruned = $uploads->pruneOlderThan(now()->subHours(max($hours, 1)));

        $this->info(
            $pruned === 0
                ? 'No unfinished disk image uploads to clean up.'
                : "Deleted {$pruned} unfinished disk image uploads.",
        );
    }
}
