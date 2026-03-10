<?php

namespace App\Enums\Activity;

enum TaskStatus: string
{
    case RUNNING = 'running';
    case STOPPED = 'stopped';
}

