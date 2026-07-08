<?php

namespace App\Exceptions\Service\Server\Allocation;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CannotModifyPrimaryDiskException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct(
            'The primary disk cannot be added, resized, or removed here — use the server build settings.',
        );
    }

    public function errorCode(): string
    {
        return 'cannot_modify_primary_disk';
    }
}
