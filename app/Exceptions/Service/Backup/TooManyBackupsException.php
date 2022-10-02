<?php

namespace Convoy\Exceptions\Service\Backup;

use Convoy\Exceptions\ConvoyException;

class TooManyBackupsException extends ConvoyException
{
    /**
     * TooManyBackupsException constructor.
     */
    public function __construct(int $backupLimit)
    {
        parent::__construct(
            sprintf('Cannot create a new backup, this server has reached its limit of %d backups.', $backupLimit)
        );
    }
}