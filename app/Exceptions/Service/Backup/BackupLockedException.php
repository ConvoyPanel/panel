<?php

namespace App\Exceptions\Service\Backup;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BackupLockedException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('Cannot delete a backup that is marked as locked.');
    }

    public function errorCode(): string
    {
        return 'backup_locked';
    }
}
