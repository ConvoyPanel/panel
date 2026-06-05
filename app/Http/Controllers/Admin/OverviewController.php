<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\ApiController;
use Convoy\Services\Admin\OverviewService;
use Convoy\Services\Admin\VersionUpdateService;
use Convoy\Transformers\Admin\OverviewTransformer;
use Illuminate\Http\JsonResponse;

class OverviewController extends ApiController
{
    public function __invoke(OverviewService $overviewService)
    {
        return fractal()
            ->item($overviewService->metrics(), new OverviewTransformer)
            ->respond();
    }

    public function refreshUpdate(
        OverviewService $overviewService,
        VersionUpdateService $versionUpdateService,
    ): JsonResponse {
        $status = $versionUpdateService->refresh();
        $overviewService->clearCache();

        return new JsonResponse([
            'data' => $status,
        ]);
    }
}
