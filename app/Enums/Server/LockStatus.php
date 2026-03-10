<?php

namespace App\Enums\Server;

enum LockStatus: string
{
    case BACKUP = 'backup';
    case CLONE = 'clone';
    case CREATE = 'create';
    case MIGRATE = 'migrate';
    case SUSPENDING = 'suspending';
    case SUSPENDED = 'suspended';
}
