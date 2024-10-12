<?php

namespace App\Enums\Server;

enum SuspensionAction: string
{
    case SUSPEND = 'suspend';
    case UNSUSPEND = 'unsuspend';
}
