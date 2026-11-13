<?php

namespace Convoy\Http\Controllers\Admin;

use Carbon\CarbonImmutable;
use Convoy\Http\Controllers\ApiController;
use Convoy\Http\Requests\Admin\UpdateTelemetrySettingsRequest;
use Convoy\Services\Telemetry\TelemetryReportBuilder;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Http\JsonResponse;

/**
 * The admin switch for the daily usage report, and a preview of the report
 * (docs/telemetry.md).
 */
class TelemetryController extends ApiController
{
    public function show(TelemetryService $telemetry): JsonResponse
    {
        return $this->present($telemetry);
    }

    public function update(UpdateTelemetrySettingsRequest $request, TelemetryService $telemetry): JsonResponse
    {
        $enabled = $request->boolean('enabled');

        // Only on a change: turning it off erases the console's copy and replaces the
        // installation id, which a repeated save must not do again.
        if ($enabled !== $telemetry->enabled()) {
            $enabled ? $telemetry->enable() : $telemetry->disable();
        }

        return $this->present($telemetry);
    }

    /**
     * The report as it would be sent right now, from the same builder the
     * daily job uses. Building it changes nothing: no counter is reset.
     */
    public function report(TelemetryReportBuilder $builder): JsonResponse
    {
        return new JsonResponse(['data' => $builder->build(CarbonImmutable::now())->payload]);
    }

    private function present(TelemetryService $telemetry): JsonResponse
    {
        return new JsonResponse(['data' => [
            'enabled' => $telemetry->enabled(),
            'disabled_by' => $telemetry->disabledBy(),
        ]]);
    }
}
