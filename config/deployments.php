<?php

return [
    // The number of minutes a deployment can run before being marked as failed.
    // Set to 0 to disable.
    'stuck_age' => env('DEPLOYMENT_STUCK_AGE', 1440),

    // The number of days to keep deployment history.
    // Set to 0 to disable.
    'retention_period' => env('DEPLOYMENT_RETENTION_PERIOD', 90),
];
