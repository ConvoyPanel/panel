<?php

namespace App\Enums\Anchor;

/**
 * The one question every console path asks an installation, with the reasons it
 * can answer no ordered from "nothing has happened yet" to "something is wrong".
 *
 * There is no "pending approval" case: a machine waiting to be let in lives in
 * `anchor_enrollments`, so the table it is in already says so. This enum only
 * ever describes something that has been admitted.
 */
enum AnchorCompatibility: string
{
    case UNENROLLED = 'unenrolled';
    case OFFLINE = 'offline';
    case INCOMPATIBLE = 'incompatible';
    case COMPATIBLE = 'compatible';
}
