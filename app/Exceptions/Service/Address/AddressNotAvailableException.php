<?php

namespace App\Exceptions\Service\Address;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AddressNotAvailableException extends ConflictHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('Only an available address can be reserved. Unassign it first if it is in use.');
    }

    public function errorCode(): string
    {
        return 'address_not_available';
    }
}
