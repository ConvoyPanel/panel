<?php

namespace App\Enums\Server;

enum DeploymentType: string
{
    case INSTALL = 'install';
    case REINSTALL = 'reinstall';
}
