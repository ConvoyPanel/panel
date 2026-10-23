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
    | counts and versions; `php artisan c:telemetry:show` prints it. Set
    | CONVOY_TELEMETRY_ENABLED=false to turn it off (the panel then asks the
    | console to erase this installation's reports), or leave the URL empty to
    | never contact the console at all.
    */
    'telemetry' => [
        'enabled' => env('CONVOY_TELEMETRY_ENABLED', true),
        'url' => env('CONVOY_TELEMETRY_URL', 'https://convoypanel.com/api/v1/telemetry'),
    ],
];
