<?php

namespace Convoy\Console\Commands\Announcements;

use Convoy\Services\Announcements\AnnouncementFeed;
use Convoy\Services\Announcements\AnnouncementService;
use Convoy\Services\Updates\ReleaseChecker;
use Illuminate\Console\Command;

class CheckAnnouncementsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'announcements:check';

    /**
     * @var string
     */
    protected $description = 'Fetch the Convoy announcements feed and the latest release, and email admins about new urgent announcements.';

    public function handle(AnnouncementFeed $feed, AnnouncementService $announcements, ReleaseChecker $releases): int
    {
        if (! $feed->enabled()) {
            $this->line('The announcements feed is off: CONVOY_ANNOUNCEMENTS_URL is empty.');
        } elseif ($feed->refresh()) {
            $count = count($announcements->all());
            $applying = count(array_filter($announcements->all(), $announcements->applies(...)));

            $this->info("The announcements feed verified: {$count} current, {$applying} apply to this panel.");
        } else {
            $this->warn(($feed->lastError()['message'] ?? 'The announcements feed could not be checked.').' The last verified copy is kept.');
        }

        // From the verified copy, so admins still hear about an urgent announcement fetched
        // before mail was configured.
        foreach ($announcements->notify() as $id) {
            $this->info("Emailed the admins about \"{$id}\".");
        }

        if (! $releases->enabled()) {
            $this->line('The release check is off: CONVOY_RELEASES_URL is empty.');
        } elseif ($releases->refresh()) {
            $this->info("The latest Convoy release is {$releases->latest()['version']}.");
        } else {
            $this->warn('The latest Convoy release could not be checked.');
        }

        return Command::SUCCESS;
    }
}
