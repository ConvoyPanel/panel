<?php

namespace Convoy\Enums\Servers\Cloudinit;

enum AuthenticationType: string
{
    case KEY = 'sshkeys';
    case PASSWORD = 'cipassword';
}