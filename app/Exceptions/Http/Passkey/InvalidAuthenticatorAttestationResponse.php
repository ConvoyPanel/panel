<?php

namespace App\Exceptions\Http\Passkey;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class InvalidAuthenticatorAttestationResponse extends BadRequestHttpException implements HasErrorCode
{
    public function __construct(Throwable $exception)
    {
        parent::__construct(
            'The given passkey could not be validated. Please check the format and try again.',
            $exception,
        );
    }

    public function errorCode(): string
    {
        return 'invalid_authenticator_attestation_response';
    }
}
