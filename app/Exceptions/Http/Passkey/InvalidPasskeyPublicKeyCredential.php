<?php

namespace App\Exceptions\Http\Passkey;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidPasskeyPublicKeyCredential extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('The given passkey is not a valid public key credential. Please check the format and try again.');
    }

    public function errorCode(): string
    {
        return 'invalid_passkey_public_key_credential';
    }
}
