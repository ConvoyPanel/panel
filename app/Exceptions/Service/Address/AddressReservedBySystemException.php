<?php

namespace App\Exceptions\Service\Address;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AddressReservedBySystemException extends ConflictHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('This address is reserved by the system and cannot be unreserved.');
    }

    public function errorCode(): string
    {
        return 'address_reserved_by_system';
    }
}
