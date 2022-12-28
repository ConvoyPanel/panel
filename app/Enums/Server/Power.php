<?php

namespace Convoy\Enums\Server;

enum Power: string
{
    case RESTART = 'reboot';
    case RESET = 'reset';
    case RESUME = 'resume';
    case SHUTDOWN = 'shutdown';
    case START = 'start';
    case KILL = 'stop';
    case SUSPEND = 'suspend';
}