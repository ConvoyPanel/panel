<?php

namespace Convoy\Http\Controllers\Base;

use Illuminate\View\View;
use Convoy\Http\Controllers\Controller;
use Illuminate\View\Factory as ViewFactory;
use Convoy\Services\Telemetry\TelemetryService;

class IndexController extends Controller
{
    /**
     * IndexController constructor.
     */
    public function __construct(
        protected ViewFactory $view,
        protected TelemetryService $telemetry,
    ) {
    }

    /**
     * Returns listing of user's servers.
     */
    public function index(): View
    {
        return $this->view->make('app', [
            'siteConfiguration' => [
                'version' => config('app.version'),
                // Whether the router reports page views (PageViewCounter).
                'telemetry' => $this->telemetry->enabled(),
            ],
        ]);
    }
}
