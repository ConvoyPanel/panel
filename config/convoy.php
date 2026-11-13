<?php

return [
    /*
|--------------------------------------------------------------------------
| Guzzle Connections
|--------------------------------------------------------------------------
|
| Configure the timeout to be used for Guzzle connections here.
*/
    'guzzle' => [
        'timeout' => env('GUZZLE_TIMEOUT', 15),
        'connect_timeout' => env('GUZZLE_CONNECT_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telemetry
    |--------------------------------------------------------------------------
    |
    | Once a day the panel sends the Convoy console an anonymous report of
    | counts and versions (docs/telemetry.md); `php artisan c:telemetry:show`
    | prints it. Admins turn it off in Settings → Telemetry, which also has the
    | console erase what it holds. CONVOY_TELEMETRY_ENABLED=false or an empty
    | URL turn it off without contacting the console.
    */
    'telemetry' => [
        'enabled' => env('CONVOY_TELEMETRY_ENABLED', true),
        'url' => env('CONVOY_TELEMETRY_URL', 'https://convoypanel.com/api/v1/telemetry'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Announcements
    |--------------------------------------------------------------------------
    |
    | Security notices and other news from the Convoy team, as one signed file
    | the panel fetches hourly (docs/announcements-feed.md). Nothing about this
    | install is sent. Leave the URL empty to turn the check off, for an
    | install that can't reach the internet.
    |
    | `keys` are the Ed25519 public keys the feed may be signed with, by key
    | id. A feed signed with any other key is refused.
    */
    'announcements' => [
        'url' => env('CONVOY_ANNOUNCEMENTS_URL', 'https://convoypanel.com/api/v1/announcements'),
        'keys' => [
            '2026-10' => 'upMG2+yt4hQ0QtmZ/3dYNcoorJafhy2dQ6OnlLAfLsU=',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Release check
    |--------------------------------------------------------------------------
    |
    | Settings → Updates compares this panel's version with the latest release
    | on GitHub, checked hourly with the announcements. Leave it empty to turn
    | the check off.
    */
    'releases' => [
        'url' => env('CONVOY_RELEASES_URL', 'https://api.github.com/repos/ConvoyPanel/panel/releases/latest'),
    ],
];
