<?php

namespace App\Http\Controllers\Base;

use App\Services\Auth\OAuthAuthenticationService;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;

class IndexController
{
    /**
     * IndexController constructor.
     */
    public function __construct(
        protected ViewFactory $view,
        protected OAuthAuthenticationService $oauth,
    ) {}

    /**
     * Returns listing of user's servers.
     */
    public function index(): View
    {
        return $this->view->make('app', [
            'siteConfiguration' => [
                'version' => config('app.version'),
                // Surfaced so the login screen can render "Continue with <provider>" buttons and
                // the account page its connect actions. Only enabled+configured providers appear.
                'oauthProviders' => collect($this->oauth->enabledProviders())
                    ->map(fn (string $label, string $id) => ['id' => $id, 'label' => $label])
                    ->values()
                    ->all(),
            ],
        ]);
    }
}
