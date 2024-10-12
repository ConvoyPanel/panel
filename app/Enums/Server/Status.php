<?php

namespace App\Enums\Server;

enum Status: string
{
    case INSTALLING = 'installing';
    case INSTALL_FAILED = 'install_failed';
    case SUSPENDED = 'suspended';
    case RESTORING_BACKUP = 'restoring_backup';
    case RESTORING_SNAPSHOT = 'restoring_snapshot';
    case DELETING = 'deleting';
    case DELETION_FAILED = 'deletion_failed';
}
