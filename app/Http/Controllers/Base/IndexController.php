<?php

namespace App\Http\Controllers\Base;

use App\Services\Auth\OAuthAuthenticationService;
use App\Services\Users\PasswordResetService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Factory as ViewFactory;

class IndexController
{
    /**
     * IndexController constructor.
     */
    public function __construct(
        protected ViewFactory $view,
        protected OAuthAuthenticationService $oauth,
        protected PasswordResetService $passwordResets,
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
                // Whether the login screen links to "Forgot password?". The endpoints answer 404
                // when this is false, so the link would only lead to an error.
                'passwordResets' => $this->passwordResets->isAvailable(),
            ],
        ]);
    }
}
