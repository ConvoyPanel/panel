<?php

namespace App\Console\Commands\Maintenance;

use App\Enums\UpdateStatus;
use App\Exceptions\Http\Admin\UpdateCheckFailedException;
use App\Services\Admin\UpdateCheckService;
use Illuminate\Console\Command;

/**
 * Refreshes what the admin area knows about the newest published release.
 *
 * Scheduled hourly. Nothing else fetches this — the endpoint the panel reads
 * only serves what this command last wrote.
 */
class CheckForUpdatesCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Check GitHub for a newer published release of the panel.';

    /**
     * @var string
     */
    protected $signature = 'updates:check';

    public function handle(UpdateCheckService $updates): int
    {
        try {
            $status = $updates->check();
        } catch (UpdateCheckFailedException $exception) {
            // The previous result is deliberately left in the cache, so the
            // admin area keeps showing the last version it heard about.
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        match ($status->status) {
            UpdateStatus::UPDATE_AVAILABLE => $this->warn(
                "An update is available: {$status->latestVersion} (running {$status->currentVersion}).",
            ),
            UpdateStatus::UP_TO_DATE => $this->info(
                "The panel is up to date ({$status->currentVersion}).",
            ),
            // A source checkout has no release to compare itself against, but
            // the fetch still happened, so record what the latest release is.
            UpdateStatus::UNKNOWN => $this->info(
                "Latest published release is {$status->latestVersion}; this panel does not report a release version.",
            ),
        };

        return self::SUCCESS;
    }
}
