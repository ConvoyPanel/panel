<?php

namespace Convoy\Exceptions\Service\Snapshot;

use Convoy\Exceptions\ConvoyException;

class TooManySnapshotsException extends ConvoyException
{
    /**
     * TooManyBackupsException constructor.
     */
    public function __construct(int $backupLimit)
    {
        parent::__construct(
            sprintf('Cannot create a new snapshot, this server has reached its limit of %d snapshots.', $backupLimit)
        );
    }
}
