<?php

namespace Convoy\Http\Controllers\Base;

use Convoy\Http\Controllers\Controller;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\View\View;

class IndexController extends Controller
{
    /**
     * IndexController constructor.
     */
    public function __construct(
        protected ViewFactory $view,
    )
    {
    }

    /**
     * Returns listing of user's servers.
     */
    public function index(): View
    {
        return $this->view->make('app', [
            'siteConfiguration' => [
                'version' => config('app.version'),
            ],
        ]);
    }
}
