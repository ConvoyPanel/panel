<?php

namespace Convoy\Exceptions\Service\Api;
use Throwable;
use Convoy\Exceptions\ConvoyException;

class InvalidJWTException extends ConvoyException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(message: 'Invalid JWT token', previous: $previous);
    }
}