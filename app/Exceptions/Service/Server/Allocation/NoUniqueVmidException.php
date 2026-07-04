<?php

namespace App\Exceptions\Service\Server\Allocation;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class NoUniqueVmidException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('There is no available VMID to use.');
    }

    public function errorCode(): string
    {
        return 'no_unique_vmid';
    }
}
