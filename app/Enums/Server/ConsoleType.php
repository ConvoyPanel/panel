<?php

namespace App\Enums\Server;

enum ConsoleType: string
{
    case NOVNC = 'novnc';
    case XTERMJS = 'xtermjs';
}
