<?php

namespace App\Exceptions\Service\Address;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InsufficientAddressesException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('There are not enough available IP addresses to satisfy the request.');
    }

    public function errorCode(): string
    {
        return 'insufficient_addresses';
    }
}
