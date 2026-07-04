<?php

namespace App\Exceptions\Service\Server\Allocation;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class NoAvailableDiskInterfaceException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct(
            'There is no available disk interface on the virtual machine to satisfy the request.',
        );
    }

    public function errorCode(): string
    {
        return 'no_available_disk_interface';
    }
}
