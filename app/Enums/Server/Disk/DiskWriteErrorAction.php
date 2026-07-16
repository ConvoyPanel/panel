<?php

namespace App\Enums\Server\Disk;

enum DiskWriteErrorAction: string
{
    case ENOSPC = 'enospc';
    case IGNORE = 'ignore';
    case REPORT = 'report';
    case STOP = 'stop';
}
