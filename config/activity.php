<?php

return [
    // The number of days ellapsed before old activity log entries are deleted.
    'prune_days' => env('APP_ACTIVITY_PRUNE_DAYS', 90),
];
