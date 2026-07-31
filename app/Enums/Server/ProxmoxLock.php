<?php

namespace App\Enums\Server;

/**
 * Proxmox's own `lock` field: the operation PVE currently has the guest locked for.
 *
 * Not a Convoy status of any kind -- it is read straight off the guest config / cluster
 * resources and tells you what the hypervisor will refuse right now. In particular its
 * `SUSPENDED` case is PVE's notion of a suspended-to-disk guest, unrelated to Convoy's
 * administrative suspension on `servers.suspended_at`.
 */
enum ProxmoxLock: string
{
    case BACKUP = 'backup';
    case CLONE = 'clone';
    case CREATE = 'create';
    case MIGRATE = 'migrate';
    case SUSPENDING = 'suspending';
    case SUSPENDED = 'suspended';
}
