<?php

namespace App\Enums\Anchor;

enum AnchorCompatibility: string
{
    case UNENROLLED = 'unenrolled';
    case OFFLINE = 'offline';
    case INCOMPATIBLE = 'incompatible';
    case COMPATIBLE = 'compatible';
}
