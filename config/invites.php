<?php

return [
    /*
     | An invite is a link that lets a brand new account establish its own credential, so the
     | panel never has to hold — or email — a password somebody else chose for them. It is the
     | reason Convoy does not send credentials the way ConvoyPanel/panel#55 asked for.
     */

    /*
     | How long a freshly issued invite stays usable, in days. Long enough to survive a weekend
     | and a spam folder, short enough that a link forwarded once and forgotten stops working.
     | Re-issuing is one click, so this errs short rather than generous.
     */
    'ttl_days' => (int) env('INVITE_TTL_DAYS', 7),
];
