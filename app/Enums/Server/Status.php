<?php

namespace Convoy\Enums\Server;

enum Status: string
{
    case INSTALLING = 'installing';
    case INSTALL_FAILED = 'install_failed';
    case SUSPENDED = 'suspended';
}
