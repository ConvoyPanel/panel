<?php

namespace Convoy\Http\Controllers\Admin\Settings;

use Convoy\Http\Controllers\ApiController;
use Convoy\Services\Announcements\AnnouncementService;
use Convoy\Services\Telemetry\TelemetryReportService;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Settings → Telemetry (docs/telemetry.md). */
class TelemetryController extends ApiController
{
    public function show(TelemetryService $telemetry): JsonResponse
    {
        return $this->present($telemetry);
    }

    public function update(Request $request, TelemetryService $telemetry): JsonResponse
    {
        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];
        $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        if ($enabled && ($variable = $telemetry->disabledBy())) {
            throw ValidationException::withMessages([
                'enabled' => "{$variable} in the panel's environment keeps telemetry off.",
            ]);
        }

        // Only on a change: turning it off erases the console's copy and replaces the
        // installation id, which a repeated save must not do again.
        if ($enabled !== $telemetry->switchedOn()) {
            $enabled ? $telemetry->enable() : $telemetry->disable();
        }

        return $this->present($telemetry);
    }

    /**
     * The report as it would be sent right now, from the same builder the daily
     * job uses. Building it changes nothing: no counter is reset.
     */
    public function report(TelemetryService $telemetry, TelemetryReportService $reports): JsonResponse
    {
        return new JsonResponse(['data' => $reports->build($telemetry->installationId())]);
    }

    /** The admin has seen the notice that telemetry is on. */
    public function dismissNotice(Request $request, AnnouncementService $announcements): JsonResponse
    {
        $announcements->dismiss($request->user(), AnnouncementService::TELEMETRY_NOTICE);

        return $this->returnNoContent();
    }

    private function present(TelemetryService $telemetry): JsonResponse
    {
        return new JsonResponse([
            'data' => [
                'enabled' => $telemetry->enabled(),
                'disabled_by' => $telemetry->disabledBy(),
                'report_time' => $telemetry->enabled() ? $telemetry->reportTime() : null,
                'reported_at' => $telemetry->reportedAt()?->toIso8601ZuluString(),
            ],
        ]);
    }
}
