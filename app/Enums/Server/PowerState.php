<?php

namespace App\Enums\Server;

/**
 * What the guest is doing right now, as observed from the hypervisor.
 *
 * Owned by Proxmox, not by us -- it changes without Convoy doing anything, and we only ever
 * read it. Distinct from {@see ServerLifecycle}, which is Convoy's own record of where the
 * server sits in provisioning, and from `servers.suspended_at`, which is an administrative
 * decision. All three are independent: a suspended, fully-installed server still reports
 * `running` for the moment before the kill lands.
 */
enum PowerState: string
{
    case STOPPED = 'stopped';
    case RUNNING = 'running';

    /* Synthetic states (not part of Proxmox but used in Convoy) */
    case STOPPING = 'stopping';
    case STARTING = 'starting';
    case SHUTTING_DOWN = 'shutting_down';
}
