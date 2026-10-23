<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Data\Admin\Settings\TelemetrySettingsData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Settings\UpdateTelemetrySettingsRequest;
use App\Services\Telemetry\TelemetryReportBuilder;
use App\Services\Telemetry\TelemetryService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class TelemetrySettingsController
{
    public function show(TelemetryService $telemetry): TelemetrySettingsData
    {
        return $this->present($telemetry);
    }

    public function update(UpdateTelemetrySettingsRequest $request, TelemetryService $telemetry): TelemetrySettingsData
    {
        $enabled = $request->boolean('enabled');

        // Only on a change: turning it off erases the console's copy and replaces the
        // installation id, which a repeated save must not do again.
        if ($enabled !== $telemetry->enabled()) {
            $enabled ? $telemetry->enable() : $telemetry->disable();

            Audit::record(AuditEvent::ADMIN_SETTINGS_TELEMETRY_UPDATED, properties: ['enabled' => $enabled]);
        }

        return $this->present($telemetry);
    }

    /**
     * The report as it would be sent right now, from the same builder the
     * daily job uses. Building it changes nothing: no counter is reset.
     */
    public function report(TelemetryReportBuilder $builder): JsonResponse
    {
        return response()->json(['data' => $builder->build(CarbonImmutable::now())->payload]);
    }

    private function present(TelemetryService $telemetry): TelemetrySettingsData
    {
        return new TelemetrySettingsData(
            enabled: $telemetry->enabled(),
            disabledBy: match (true) {
                ! config('convoy.telemetry.enabled') => 'CONVOY_TELEMETRY_ENABLED',
                blank(config('convoy.telemetry.url')) => 'CONVOY_TELEMETRY_URL',
                default => null,
            },
        );
    }
}
