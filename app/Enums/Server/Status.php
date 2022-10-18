<?php

namespace Convoy\Enums\Server;

enum Status: string
{
    case INSTALLING = 'installing';
    case SUSPENDED = 'suspended';
}
