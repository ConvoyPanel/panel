<?php

return [

    'connections' => [

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            // A job still running after this many seconds is handed to a second worker, so it has
            // to outlast every job's timeout, Horizon's default included. Laravel's is 90.
            'retry_after' => 330,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

];
