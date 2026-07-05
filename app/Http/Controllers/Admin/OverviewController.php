<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\Overview\OverviewData;
use App\Services\Admin\OverviewService;

class OverviewController
{
    public function __invoke(OverviewService $overview): OverviewData
    {
        return $overview->metrics();
    }
}
