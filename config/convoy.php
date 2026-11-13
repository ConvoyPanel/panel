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
];
