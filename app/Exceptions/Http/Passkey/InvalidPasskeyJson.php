<?php

namespace App\Exceptions\Http\Passkey;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidPasskeyJson extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('The given passkey should be formatted as json. Please check the format and try again.');
    }

    public function errorCode(): string
    {
        return 'invalid_passkey_json';
    }
}
