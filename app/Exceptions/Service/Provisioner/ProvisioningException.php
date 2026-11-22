<?php

namespace App\Exceptions\Service\Provisioner;

use RuntimeException;

/**
 * A server's provisioner scripts could not be put on its node.
 *
 * Thrown from the deployment step, so the message is what the operator reads
 * on the failed build.
 */
class ProvisioningException extends RuntimeException {}
