<?php

namespace App\Exceptions\Http\Auth;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidAuthenticationMethodException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('No valid authentication method provided.');
    }

    public function errorCode(): string
    {
        return 'invalid_authentication_method';
    }
}
