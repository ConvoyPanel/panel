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

    /*
     | Generic OpenID Connect. `base_url` is the IdP issuer; the authorize/token/userinfo
     | endpoints are discovered from `{base_url}/.well-known/openid-configuration`, so for a
     | standards-compliant IdP (Keycloak, Authentik, Okta, Auth0, Azure AD, …) an operator
     | only needs the issuer plus a client id/secret. The three *_url overrides pin an
     | endpoint explicitly for IdPs whose discovery is non-standard. `scopes` (comma list)
     | overrides the requested scopes; `openid` is always included regardless.
     */
    'oidc' => [
        'client_id' => env('OAUTH_OIDC_CLIENT_ID'),
        'client_secret' => env('OAUTH_OIDC_CLIENT_SECRET'),
        'redirect' => env('OAUTH_OIDC_REDIRECT_URI', '/api/auth/oauth/oidc/callback'),
        'base_url' => env('OAUTH_OIDC_BASE_URL'),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('OAUTH_OIDC_SCOPES', 'profile,email')),
        ))),
        'auth_url' => env('OAUTH_OIDC_AUTH_URL'),
        'token_url' => env('OAUTH_OIDC_TOKEN_URL'),
        'userinfo_url' => env('OAUTH_OIDC_USERINFO_URL'),
    ],

];
