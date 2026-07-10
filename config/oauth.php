<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OAuth / OIDC single sign-on (Relying Party)
    |--------------------------------------------------------------------------
    |
    | Convoy acts as a Relying Party: users authenticate against an external
    | identity provider (Google, GitHub, GitLab, …) via Laravel Socialite and
    | are dropped into a Convoy session. This is OPTIONAL — a provider only
    | appears on the login screen once it is both listed below and configured
    | with credentials in `config/services.php`, so an operator who sets no
    | client id/secret gets the plain email/password + passkey login unchanged.
    |
    | This is distinct from the signed-URL SSO deep link (config/sso.php): that
    | is an admin/integration-initiated link that drops an already-authenticated
    | user in; this is standards-based federated login the user drives.
    |
    */

    /*
     | The providers Convoy may authenticate against. Keyed by the Socialite
     | driver name. `label` is the button text ("Continue with <label>"); a
     | provider is only surfaced/usable when its `enabled` flag is true AND the
     | matching `config/services.php` block has a client id + secret.
     */
    'providers' => [
        'google' => [
            'enabled' => (bool) env('OAUTH_GOOGLE_ENABLED', false),
            'label' => 'Google',
        ],
        'github' => [
            'enabled' => (bool) env('OAUTH_GITHUB_ENABLED', false),
            'label' => 'GitHub',
        ],
        'gitlab' => [
            'enabled' => (bool) env('OAUTH_GITLAB_ENABLED', false),
            'label' => 'GitLab',
        ],
    ],

    /*
     | When true, a successful sign-in from a provider whose identity does not
     | match any existing user (by connection or verified email) provisions a
     | brand-new, non-admin Convoy account. Off by default: most panels want a
     | closed door where only pre-existing users may federate. Auto-provisioned
     | users never receive `root_admin`.
     */
    'registration' => (bool) env('OAUTH_REGISTRATION', false),

    /*
     | When true, a provider identity whose verified email matches an existing
     | Convoy user is linked to that account on first sign-in (so an operator
     | can pre-create users and let them "Continue with Google" without an
     | explicit link step). Only honoured when the provider asserts the email
     | is verified. Off flips to "explicit link only" (users must connect the
     | provider from their account security page while logged in).
     */
    'link_by_verified_email' => (bool) env('OAUTH_LINK_BY_VERIFIED_EMAIL', true),

];
