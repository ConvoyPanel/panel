<?php

namespace App\Exceptions\Service\Api;

use App\Exceptions\ConvoyException;
use Throwable;

class InvalidJWTException extends ConvoyException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(message: 'Invalid JWT token', previous: $previous);
    }
}
