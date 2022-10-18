<?php

namespace Convoy\Enums\Server;

enum ThrottleAction: string
{
    case THROTTLE = 'throttle';
    case UNTHROTTLE = 'unthrottle';
}