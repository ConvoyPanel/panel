<?php

namespace App\Enums\Server;

enum ServerStatus: string
{
    case READY = 'ready';
    case DEFERRED_OS_SELECTION = 'deferred_os_selection';
    case INSTALLING = 'installing';
    case INSTALL_FAILED = 'install_failed';
    case SUSPENDED = 'suspended';
    case RESTORING_BACKUP = 'restoring_backup';
    case RESTORING_SNAPSHOT = 'restoring_snapshot';
    case DELETING = 'deleting';
    case DELETION_FAILED = 'deletion_failed';
}
