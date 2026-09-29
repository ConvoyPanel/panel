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

        /*
        | How much of a disk image travels in one request.
        |
        | 16 MiB rather than something larger because the ceiling is not PHP's:
        | Cloudflare rejects a request body over 100 MB on most plans, and a
        | panel behind it would fail on every image worth uploading. A chunk
        | this size also caps what a dropped connection costs to re-send.
        |
        | Whatever sits in front of the panel has to accept a body this large:
        | `client_max_body_size 20m;` on nginx, and the equivalent elsewhere.
        */
        'upload_chunk_bytes' => env('ARTIFACTS_UPLOAD_CHUNK_BYTES', 16 * 1024 * 1024),

        /*
        | Where a half-arrived upload is assembled. Local even when `disk` is
        | remote: chunks are appended, and an object store has no append. The
        | finished file is streamed onto `disk` once, at the end.
        */
        'upload_disk' => env('ARTIFACTS_UPLOAD_DISK', 'local'),

        /*
        | How long an unfinished upload keeps its bytes before it is swept.
        | Long enough to survive a laptop closing overnight, short enough that
        | an abandoned 10 GB image does not live on the disk forever.
        */
        'upload_ttl_hours' => env('ARTIFACTS_UPLOAD_TTL_HOURS', 24),
    ],

    /*
    | The image catalogue the admin area can import from.
    |
    | A registry is a catalogue, not a concept: the panel reads this URL, shows
    | what it lists, and copies an entry into an image definition on request.
    | Nothing is subscribed to and nothing syncs on a schedule. Point it at your
    | own published registry.json to replace the default catalogue.
    */
    'registry' => [
        'url' => env('IMAGE_REGISTRY_URL', 'https://cofoundry.cdn.convoypanel.com/registry.json'),
        'cache_minutes' => env('IMAGE_REGISTRY_CACHE_MINUTES', 30),
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
