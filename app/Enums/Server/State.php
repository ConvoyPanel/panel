<?php

namespace App\Enums\Server;

enum State: string
{
    case STOPPED = 'stopped';
    case RUNNING = 'running';

    /* Synthetic states (not part of Proxmox but used in Convoy) */
    case STOPPING = 'stopping';
    case STARTING = 'starting';
    case SHUTTING_DOWN = 'shutting_down';
}
