<?php

namespace App\Exceptions\Http\Auth;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidPasskeyException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('The provided passkey is invalid. Please try again or use an alternative login method.');
    }

    public function errorCode(): string
    {
        return 'invalid_passkey';
    }
}
