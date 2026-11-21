<?php

namespace App\Enums\Provisioner;

/**
 * Where one server's provisioner scripts have got to.
 *
 * Only `pending` is open. A late report can still turn `timed_out` into a
 * result, because a timeout is the panel giving up on waiting, not proof the
 * scripts never ran.
 */
enum ProvisioningStatus: string
{
    /** The snippet is on the node and the guest has not reported yet. */
    case PENDING = 'pending';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case TIMED_OUT = 'timed_out';
    /** Superseded by a rebuild, or the server was deleted, before it reported. */
    case CANCELLED = 'cancelled';

    public function acceptsReport(): bool
    {
        return $this === self::PENDING || $this === self::TIMED_OUT;
    }
}
