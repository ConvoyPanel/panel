<?php

namespace App\Exceptions\Service\Network;

use App\Exceptions\DisplayException;

class AddressInUseException extends DisplayException
{
    /**
     * TooManyBackupsException constructor.
     */
    public function __construct(int $addressId)
    {
        parent::__construct(
            sprintf('Address %d is currently in use by another server.', $addressId),
        );
    }
}
