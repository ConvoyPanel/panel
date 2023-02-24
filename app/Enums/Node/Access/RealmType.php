<?php

namespace Convoy\Enums\Node\Access;

enum RealmType: string
{
    case PAM = 'pam';
    case PVE = 'pve';
}
