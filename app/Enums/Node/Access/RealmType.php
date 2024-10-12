<?php

namespace App\Enums\Node\Access;

enum RealmType: string
{
    case PAM = 'pam';
    case PVE = 'pve';
}
