<?php

return [
    /*
     | Single sign-on deep links let an external integration (e.g. a WHMCS module) drop a user
     | straight into Convoy without handling their password. The admin/application API mints a
     | short-lived Laravel *signed URL* (`URL::temporarySignedRoute`), scoped by the minting token's
     | abilities; the browser is redirected to it, the `signed` middleware verifies the HMAC, and a
     | one-time nonce guards against replay. This replaces the previous bespoke app-key-JWT hack.
     */

    /*
     | How long a freshly minted SSO link stays valid, in seconds. The integration is expected to
     | mint the link and immediately redirect the browser to it, so this only needs to cover the
     | round trip plus a little clock slack. Kept short to limit the replay window.
     */
    'link_ttl' => (int) env('SSO_LINK_TTL', 60),

    /*
     | The log channel every successful SSO consumption is written to. SSO bypasses password/2FA,
     | so each login is audited. Defaults to the application's default channel; point it at a
     | dedicated channel (e.g. a separate file) if you want an isolated audit trail.
     */
    'audit_channel' => env('SSO_AUDIT_CHANNEL', env('LOG_CHANNEL', 'stack')),
];
