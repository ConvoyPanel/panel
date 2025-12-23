<?php

namespace App\Enums\Activity;

enum TaskExitStatus: string
{
    case OK = 'OK';
    case WARNINGS = 'WARNINGS';
}
