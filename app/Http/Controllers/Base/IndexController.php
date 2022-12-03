<?php

namespace Convoy\Http\Controllers\Base;

use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;
use Convoy\Http\Controllers\Controller;

class IndexController extends Controller
{
    /**
     * IndexController constructor.
     */
    public function __construct(
        protected ViewFactory $view
    ) {
    }

    /**
     * Returns listing of user's servers.
     */
    public function index(): View
    {
        return $this->view->make('app', [
            'siteConfiguration' => [
                'version' => config('app.version')
            ]
        ]);
    }
}