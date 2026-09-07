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
    /*
    | Where an uploaded file the panel hosts lives -- a disk image or an ISO --
    | and how long the URL a node fetches it with stays valid. The link only has to survive one download, so it is
    | minted per fetch and expires soon after -- a node never holds a credential
    | for the panel's storage, and a leaked URL is worthless by the time anyone
    | finds it.
    */
    'artifacts' => [
        'disk' => env('ARTIFACTS_DISK', 'artifacts'),
        'url_ttl_minutes' => env('ARTIFACTS_URL_TTL_MINUTES', 120),
    ],

    /*
    | Where uploaded profile pictures live. Not the `artifacts` disk: those are
    | multi-gigabyte files a node fetches once over an expiring link, while an
    | avatar is a 30KB file the panel serves on every page. Point this at `s3`
    | to move them off the box.
    */
    'avatars' => [
        'disk' => env('AVATARS_DISK', 'local'),
    ],

    'updates' => [
        'repository' => env('UPDATE_CHECK_REPOSITORY', 'ConvoyPanel/panel'),
    ],
];
