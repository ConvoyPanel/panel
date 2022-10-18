<?php

namespace Convoy\Enums\Server;

enum SuspensionAction: string
{
    case SUSPEND = 'suspend';
    case UNSUSPEND = 'unsuspend';
}