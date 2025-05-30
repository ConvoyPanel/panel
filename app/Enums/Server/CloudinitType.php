<?php

namespace App\Enums\Server;

enum CloudinitType: string
{
    case CONFIG_DRIVE_2 = 'configdrive2';
    case NO_CLOUD = 'nocloud';
    case OPEN_NEBULA = 'opennebula';
}
