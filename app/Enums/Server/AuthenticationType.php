<?php

namespace Convoy\Enums\Server;

enum AuthenticationType: string
{
    case KEY = 'ssh_keys';
    case PASSWORD = 'password';
}
