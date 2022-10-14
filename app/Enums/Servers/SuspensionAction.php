<?php

namespace Convoy\Enums\Servers;

enum SuspensionAction: string
{
    case SUSPEND = 'suspend';
    case UNSUSPEND = 'unsuspend';
}