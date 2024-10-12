<?php

namespace App\Enums\Server;

enum BackupMode: string
{
    case SNAPSHOT = 'snapshot';
    case SUSPEND = 'suspend';
    case KILL = 'kill';
}
