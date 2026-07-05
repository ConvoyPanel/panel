<?php

namespace App\Http\Controllers\Base;

use Illuminate\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;

class IndexController
{
    /**
     * IndexController constructor.
     */
    public function __construct(
        protected ViewFactory $view,
    ) {}

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
