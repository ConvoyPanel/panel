<?php

namespace App\Enums\Anchor;

/**
 * The one question every console path asks an Anchor, with the reasons it can
 * answer no ordered from "nothing has happened yet" to "something is wrong".
 */
enum AnchorCompatibility: string
{
    case UNENROLLED = 'unenrolled';

    /** Introduced itself with a valid key and is waiting to be let in. */
    case PENDING_APPROVAL = 'pending_approval';
    case OFFLINE = 'offline';
    case INCOMPATIBLE = 'incompatible';
    case COMPATIBLE = 'compatible';
}
