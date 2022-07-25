<?php

namespace App\Enums\Proxmox;

enum AuthenticationType: string
{
    case PVE = 'pve'; // use PVE if the user doesn't exist as a Linux user
    case PAM = 'pam'; // use PAM only if the user exists as a Linux user
}