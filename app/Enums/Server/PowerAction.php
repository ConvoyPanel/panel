<?php

namespace App\Enums\Server;

enum PowerAction: string
{
    case RESTART = 'restart';
    case RESET = 'reset';
    case RESUME = 'resume';
    case SHUTDOWN = 'shutdown';
    case START = 'start';
    case KILL = 'kill';
    case SUSPEND = 'suspend';
}
