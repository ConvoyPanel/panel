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

    public function isReady(): bool
    {
        return $this === self::READY;
    }

    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    public function isInstalling(): bool
    {
        return $this === self::INSTALLING;
    }

    public function isInstalled(): bool
    {
        return ! in_array($this, [
            self::INSTALLING,
            self::DEFERRED_OS_SELECTION,
        ]);
    }

    public function isBusy(): bool
    {
        return match ($this) {
            self::INSTALLING,
            self::RESTORING_BACKUP,
            self::RESTORING_SNAPSHOT,
            self::DELETING => true,
            default => false,
        };
    }
}
