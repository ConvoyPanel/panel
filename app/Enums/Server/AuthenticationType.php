<?php

namespace Convoy\Enums\Server;

enum AuthenticationType: string
{
    case KEY = 'sshkeys';
    case PASSWORD = 'cipassword';
}
