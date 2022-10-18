<?php

namespace Convoy\Enums\Server\Cloudinit;

enum AuthenticationType: string
{
    case KEY = 'sshkeys';
    case PASSWORD = 'cipassword';
}
