<?php

namespace Convoy\Services\Announcements;

use RuntimeException;

/** The feed didn't verify or isn't the shape docs/announcements-feed.md describes. */
class InvalidFeedException extends RuntimeException
{
}
