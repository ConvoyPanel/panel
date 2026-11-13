<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\ApiController;
use Convoy\Services\Announcements\Announcement;
use Convoy\Services\Announcements\AnnouncementService;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends ApiController
{
    /**
     * What every admin page shows: critical announcements as a banner, the rest
     * on the overview, and whether this admin still has to see the notice that
     * telemetry is on.
     */
    public function index(Request $request, AnnouncementService $announcements, TelemetryService $telemetry): JsonResponse
    {
        $user = $request->user();
        $shown = $announcements->forAdmin($user);

        return new JsonResponse([
            'data' => [
                'banner' => array_map(self::present(...), $shown['banner']),
                'overview' => array_map(self::present(...), $shown['overview']),
                'telemetry_notice' => $telemetry->enabled()
                    && ! in_array(AnnouncementService::TELEMETRY_NOTICE, $announcements->dismissedBy($user), true),
            ],
        ]);
    }

    public function dismiss(Request $request, AnnouncementService $announcements, string $announcementId): JsonResponse
    {
        $announcement = $announcements->find($announcementId);

        if ($announcement?->critical() && $announcements->applies($announcement)) {
            return new JsonResponse([
                'errors' => [[
                    'code' => 'CriticalAnnouncement',
                    'status' => '422',
                    'detail' => 'A critical announcement stays until it no longer applies to this panel.',
                ]],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $announcements->dismiss($request->user(), $announcementId);

        return $this->returnNoContent();
    }

    /** @return array<string, mixed> */
    public static function present(Announcement $announcement): array
    {
        return $announcement->toArray();
    }
}
