<?php

namespace App\Exceptions\Http\Api;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidIdempotencyKeyException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('An Idempotency-Key must be between 1 and 255 characters.');
    }

    public function errorCode(): string
    {
        return 'idempotency_key_invalid';
    }
}
