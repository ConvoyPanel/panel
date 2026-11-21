<?php

namespace App\Http\Controllers\Client;

use App\Http\Requests\Client\RecordPageViewsRequest;
use App\Services\Telemetry\PageKeys;
use App\Services\Telemetry\TelemetryService;
use App\Services\Telemetry\ViewCounter;
use Illuminate\Http\Response;

/**
 * Page views for the telemetry report, sent in batches by the browser (see
 * `features/telemetry/page-views.ts`). Only counts are kept: not who viewed the
 * page, nor which server or node it was about.
 */
class PageViewController
{
    public function __invoke(
        RecordPageViewsRequest $request,
        TelemetryService $telemetry,
        PageKeys $pages,
        ViewCounter $views,
    ): Response {
        if (! $telemetry->enabled()) {
            return response()->noContent();
        }

        foreach ($request->views() as $routeId => $count) {
            $key = $pages->for($routeId);

            if ($key !== null) {
                $views->record($key, $count);
            }
        }

        return response()->noContent();
    }
}
