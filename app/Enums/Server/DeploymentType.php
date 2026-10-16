<?php

namespace App\Enums\Server;

enum DeploymentType: string
{
    case INSTALL = 'install';
    case REINSTALL = 'reinstall';
    case IMPORT = 'import';

    /**
     * Moving an existing guest to another member of its cluster. Reuses the
     * deployment machinery for its steps and progress rather than growing a
     * second one -- the rebind itself is two columns, and the audit log already
     * records from, to and vmid.
     */
    case MIGRATE = 'migrate';
    case DELETE = 'delete';
}
