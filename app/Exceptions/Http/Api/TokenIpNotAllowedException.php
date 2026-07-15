<?php

namespace App\Exceptions\Http\Api;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TokenIpNotAllowedException extends AccessDeniedHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('This token cannot be used from this IP address.');
    }

    public function errorCode(): string
    {
        return 'token_ip_not_allowed';
    }
}
