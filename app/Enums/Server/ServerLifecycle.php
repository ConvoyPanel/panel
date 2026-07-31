<?php

namespace App\Enums\Server;

/**
 * Where a server sits in its provisioning lifecycle, as recorded by Convoy.
 *
 * Owned by us: nothing outside Convoy writes it, and it never changes on its own. Distinct
 * from {@see PowerState}, which is the guest's live power state as reported by Proxmox.
 *
 * Suspension is deliberately *not* a case here. It is an administrative decision that
 * coexists with any lifecycle stage rather than replacing one -- it lives on
 * `servers.suspended_at`, and neither axis is derived from the other.
 */
enum ServerLifecycle: string
{
    case READY = 'ready';
    case DEFERRED_OS_SELECTION = 'deferred_os_selection';
    case INSTALLING = 'installing';
    case INSTALL_FAILED = 'install_failed';
    case RESTORING_BACKUP = 'restoring_backup';
    case DELETING = 'deleting';
    case DELETION_FAILED = 'deletion_failed';

    public function isReady(): bool
    {
        return $this === self::READY;
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
            self::DELETING => true,
            default => false,
        };
    }
}
