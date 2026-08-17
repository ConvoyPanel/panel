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
| Update Checker
|--------------------------------------------------------------------------
|
| The GitHub repository whose published releases the panel compares itself
| against. Only forks that cut their own releases need to change this.
*/
    'updates' => [
        'repository' => env('UPDATE_CHECK_REPOSITORY', 'ConvoyPanel/panel'),
    ],
];
