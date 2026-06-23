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

    'credentials_mail' => [
        'users' => [
            'enabled' => (bool) env('CONVOY_SEND_USER_CREDENTIAL_EMAILS', false),
        ],

        'servers' => [
            'enabled' => (bool) env('CONVOY_SEND_SERVER_CREDENTIAL_EMAILS', false),
        ],
    ],
];
