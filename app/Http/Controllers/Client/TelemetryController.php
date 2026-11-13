<?php

namespace Convoy\Http\Controllers\Client;

use Convoy\Http\Controllers\ApiController;
use Convoy\Services\Telemetry\PageViewCounter;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelemetryController extends ApiController
{
    /**
     * Counts a page view for the daily telemetry report. The router sends the
     * page's route pattern; one PageViewCounter doesn't list is ignored.
     */
    public function storeView(Request $request, TelemetryService $telemetry, PageViewCounter $views): JsonResponse
    {
        $data = $request->validate([
            'page' => ['required', 'string', 'max:191'],
        ]);

        if ($telemetry->enabled()) {
            $views->record($data['page']);
        }

        return $this->returnNoContent();
    }
}
