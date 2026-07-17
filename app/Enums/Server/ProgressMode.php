<?php

namespace App\Enums\Server;

/**
 * How a deployment step's progress should be read. A determinate step has a
 * meaningful current/total the UI can draw as a bar; an indeterminate step only
 * knows it is working, so the UI shows a spinner and no percentage.
 */
enum ProgressMode: string
{
    case DETERMINATE = 'determinate';
    case INDETERMINATE = 'indeterminate';
}
