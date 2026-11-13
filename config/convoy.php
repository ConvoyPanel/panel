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
    | The anonymous daily usage report (docs/telemetry.md lists every field).
    | `enabled` off, or an empty `url` for a panel that can't reach the
    | internet, stops it outright: the admin switch then shows it as off and
    | can't turn it back on.
    |
    | `default` is the admin switch's position until an admin changes it.
    | Setting it to false makes telemetry opt-in.
    */
    'telemetry' => [
        'default' => true,
        'enabled' => (bool) env('CONVOY_TELEMETRY_ENABLED', true),
        'url' => env('CONVOY_TELEMETRY_URL', 'https://convoypanel.com/api/v1/telemetry'),
    ],
];
