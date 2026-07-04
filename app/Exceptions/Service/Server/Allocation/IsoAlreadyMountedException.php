<?php

namespace App\Exceptions\Service\Server\Allocation;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IsoAlreadyMountedException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('The requested ISO is already mounted');
    }

    public function errorCode(): string
    {
        return 'iso_already_mounted';
    }
}
