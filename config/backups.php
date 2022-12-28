<?php

return [
    // The time to wait before automatically failing a backup, time is in minutes and defaults
    // to 6 hours.  To disable this feature, set the value to `0`.
    'prune_age' => env('BACKUP_PRUNE_AGE', 360),

    // Defines the backup creation throttle limits for users. In this default example, we allow
    // a user to create two (successful or pending) backups per 10 minutes. Even if they delete
    // a backup it will be included in the throttle count.
    //
    // Set the period to "0" to disable this throttle. The period is defined in seconds.
    'throttles' => [
        'limit' => env('BACKUP_THROTTLE_LIMIT', 2),
        'period' => env('BACKUP_THROTTLE_PERIOD', 600),
    ],
];