<?php

namespace Convoy\Http\Controllers\Admin\Settings;

use Convoy\Http\Controllers\Admin\AnnouncementController;
use Convoy\Http\Controllers\ApiController;
use Convoy\Services\Announcements\Announcement;
use Convoy\Services\Announcements\AnnouncementFeed;
use Convoy\Services\Announcements\AnnouncementService;
use Convoy\Services\Announcements\VersionRanges;
use Convoy\Services\Updates\ReleaseChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Settings → Updates: this panel's versions, the latest release, and every
 * current announcement with the reason it does or doesn't apply.
 */
class UpdatesController extends ApiController
{
    public function show(
        Request $request,
        AnnouncementService $announcements,
        AnnouncementFeed $feed,
        ReleaseChecker $releases,
    ): JsonResponse {
        $cached = $feed->cached();
        $dismissed = $announcements->dismissedBy($request->user());

        return new JsonResponse([
            'data' => [
                'panel' => [
                    'version' => $announcements->panelVersion(),
                    'php' => $announcements->phpVersion(),
                    'from_source' => ! VersionRanges::comparable($announcements->panelVersion()),
                ],
                'release' => [
                    'enabled' => $releases->enabled(),
                    'latest' => $releases->latest(),
                    'update_available' => $releases->updateAvailable(),
                ],
                'feed' => [
                    'enabled' => $feed->enabled(),
                    'generated_at' => $cached['generated_at'] ?? null,
                    'checked_at' => $cached['checked_at'] ?? null,
                    'error' => $feed->lastError(),
                ],
                'announcements' => array_map(fn (Announcement $announcement) => [
                    ...AnnouncementController::present($announcement),
                    'applies' => $announcements->applies($announcement),
                    'reasons' => $announcements->reasons($announcement),
                    'dismissed' => in_array($announcement->id, $dismissed, true),
                ], $announcements->all()),
            ],
        ]);
    }

    /** Runs the hourly check now: the feed, the mail it may send, and the release check. */
    public function check(
        Request $request,
        AnnouncementService $announcements,
        AnnouncementFeed $feed,
        ReleaseChecker $releases,
    ): JsonResponse {
        Artisan::call('announcements:check');

        return $this->show($request, $announcements, $feed, $releases);
    }
}
