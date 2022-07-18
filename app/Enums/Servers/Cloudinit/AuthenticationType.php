<?php

namespace App\Enums\Servers\Cloudinit;

enum AuthenticationType: string
{
    case KEY = 'sshkeys';
    case PASSWORD = 'cipassword';
}