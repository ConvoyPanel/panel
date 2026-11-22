<?php

namespace App\Exceptions\Http\Api;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class IdempotencyKeyReusedException extends UnprocessableEntityHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('This Idempotency-Key was already used for a different request.');
    }

    public function errorCode(): string
    {
        return 'idempotency_key_reused';
    }
}
