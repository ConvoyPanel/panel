<?php

namespace App\Exceptions\Service\Anchor;

use RuntimeException;

/**
 * An Anchor refused a call, or could not be reached to make one.
 *
 * Thrown from inside queued jobs rather than from a request, so it is a plain
 * runtime exception: the deployment step records the message and the chain's
 * rollback runs. The message is written to be read by the operator whose
 * migration just stopped, because it is the only thing they will see.
 */
class AnchorRequestException extends RuntimeException {}
