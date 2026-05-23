<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\ApiController;
use Convoy\Services\Admin\OverviewService;
use Convoy\Transformers\Admin\OverviewTransformer;

class OverviewController extends ApiController
{
    public function __invoke(OverviewService $overviewService)
    {
        return fractal()
            ->item($overviewService->metrics(), new OverviewTransformer)
            ->respond();
    }
}
