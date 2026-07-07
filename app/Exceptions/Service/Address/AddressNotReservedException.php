<?php

namespace App\Exceptions\Service\Address;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AddressNotReservedException extends ConflictHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('This address is not reserved.');
    }

    public function errorCode(): string
    {
        return 'address_not_reserved';
    }
}
