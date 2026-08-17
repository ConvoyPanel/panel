<?php

namespace App\Enums;

enum UpdateStatus: string
{
    case UP_TO_DATE = 'up_to_date';
    case UPDATE_AVAILABLE = 'update_available';

    /**
     * Either nothing has been checked yet, or the running version is not a
     * release (a source checkout reports `canary`) and so cannot be compared
     * against one.
     */
    case UNKNOWN = 'unknown';
}
