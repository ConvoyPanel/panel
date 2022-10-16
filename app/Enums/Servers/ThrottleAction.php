<?php

namespace Convoy\Enums\Servers;

enum ThrottleAction: string
{
    case THROTTLE = 'throttle';
    case UNTHROTTLE = 'unthrottle';
}