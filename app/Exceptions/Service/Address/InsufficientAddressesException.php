<?php

namespace App\Exceptions\Service\Address;

use App\Exceptions\DisplayException;

class InsufficientAddressesException extends DisplayException
{
    public function __construct()
    {
        parent::__construct('There are not enough available IP addresses to satisfy the request.');
    }
}
