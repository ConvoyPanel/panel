<?php

namespace Convoy\Enums\Server;

enum DeploymentType: string
{
    case INSTALL = 'install';
    case REINSTALL = 'reinstall';
}
