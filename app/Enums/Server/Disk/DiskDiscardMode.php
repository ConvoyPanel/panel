<?php

namespace App\Enums\Server\Disk;

enum DiskDiscardMode: string
{
    case IGNORE = 'ignore';

    case ON = 'on';
}
