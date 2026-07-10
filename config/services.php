<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    /*
     | OAuth / OIDC Relying-Party providers (see config/oauth.php). `redirect`
     | is a path that Socialite resolves against APP_URL; it must match the
     | `auth.oauth.callback` route and the URI registered with the provider.
     */
    'google' => [
        'client_id' => env('OAUTH_GOOGLE_CLIENT_ID'),
        'client_secret' => env('OAUTH_GOOGLE_CLIENT_SECRET'),
        'redirect' => env('OAUTH_GOOGLE_REDIRECT_URI', '/api/auth/oauth/google/callback'),
    ],

    'github' => [
        'client_id' => env('OAUTH_GITHUB_CLIENT_ID'),
        'client_secret' => env('OAUTH_GITHUB_CLIENT_SECRET'),
        'redirect' => env('OAUTH_GITHUB_REDIRECT_URI', '/api/auth/oauth/github/callback'),
    ],

    'gitlab' => [
        'client_id' => env('OAUTH_GITLAB_CLIENT_ID'),
        'client_secret' => env('OAUTH_GITLAB_CLIENT_SECRET'),
        'redirect' => env('OAUTH_GITLAB_REDIRECT_URI', '/api/auth/oauth/gitlab/callback'),
    ],

];
