<?php

namespace Convoy\Enums\Servers;

enum Status: string
{
    case INSTALLING = 'installing';
    case SUSPENDED = 'suspended';
}
