<?php

namespace App\Exceptions\Service\Server\Allocation;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CannotShrinkDiskException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('A disk can only be grown, not shrunk.');
    }

    public function errorCode(): string
    {
        return 'cannot_shrink_disk';
    }
}
