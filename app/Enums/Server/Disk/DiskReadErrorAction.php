<?php

namespace App\Enums\Server\Disk;

enum DiskReadErrorAction: string
{
    case IGNORE = 'ignore';
    case REPORT = 'report';
    case STOP = 'stop';
}