<?php

namespace App\Exceptions\Http\Auth;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OAuthProviderNotEnabledException extends NotFoundHttpException implements HasErrorCode
{
    public function __construct(string $provider)
    {
        parent::__construct("The \"{$provider}\" single sign-on provider is not enabled.");
    }

    public function errorCode(): string
    {
        return 'oauth_provider_not_enabled';
    }
}
