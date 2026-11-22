<?php

namespace App\Exceptions\Http\Api;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class IdempotencyKeyInProgressException extends ConflictHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('A request with this Idempotency-Key is still being processed.', headers: ['Retry-After' => '1']);
    }

    public function errorCode(): string
    {
        return 'idempotency_key_in_progress';
    }
}
