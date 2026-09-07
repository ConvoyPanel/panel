<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | The `artifacts` disk is where a file the operator has nowhere else to host
    | lives -- a disk image, or an ISO. It is the panel's answer to "I have a
    | file and no CDN": upload it, and the panel serves it over an expiring
    | signed URL that a node fetches once, when it first needs it.
    |
    | `serve => true` is what makes those signed URLs work on local disk, so the
    | zero-infrastructure default needs no object store at all. An operator who
    | outgrows serving multi-gigabyte files from the panel points ARTIFACTS_DISK
    | at `s3` and nothing above this file changes -- the panel still mints the
    | URL, it just resolves somewhere else. That swap needs
    | `league/flysystem-aws-s3-v3`, which is not installed by default.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'artifacts' => [
            'driver' => 'local',
            'root' => storage_path('app/artifacts'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
