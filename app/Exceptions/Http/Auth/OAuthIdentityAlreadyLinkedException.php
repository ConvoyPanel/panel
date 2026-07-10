<?php

namespace App\Exceptions\Http\Auth;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class OAuthIdentityAlreadyLinkedException extends ConflictHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('That single sign-on identity is already linked to a different Convoy account.');
    }

    public function errorCode(): string
    {
        return 'oauth_identity_already_linked';
    }
}
