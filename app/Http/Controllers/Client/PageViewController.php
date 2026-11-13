<?php

namespace Convoy\Http\Controllers\Client;

use Convoy\Http\Controllers\ApiController;
use Convoy\Http\Requests\Client\RecordPageViewsRequest;
use Convoy\Services\Telemetry\PageKeys;
use Convoy\Services\Telemetry\TelemetryService;
use Convoy\Services\Telemetry\ViewCounter;
use Illuminate\Http\Response;

/**
 * Page views for the telemetry report, sent in batches by the browser (see
 * `lib/telemetry/pageViews.ts`). Only counts are kept: not who viewed the
 * page, nor which server or node it was about.
 */
class PageViewController extends ApiController
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

        foreach ($request->views() as $pattern => $count) {
            $key = $pages->for($pattern);

            if ($key !== null) {
                $views->record($key, $count);
            }
        }

        return response()->noContent();
    }
}
