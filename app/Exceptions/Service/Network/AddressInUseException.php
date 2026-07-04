<?php

namespace App\Exceptions\Service\Network;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AddressInUseException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct(int $addressId)
    {
        parent::__construct(
            sprintf('Address %d is currently in use by another server.', $addressId),
        );
    }

    public function errorCode(): string
    {
        return 'address_in_use';
    }
}
