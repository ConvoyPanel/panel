<?php

namespace App\Exceptions\Http\Auth;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OAuthAccountNotProvisionedException extends AccessDeniedHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('No Convoy account is linked to that identity. Ask an administrator to create your account or connect the provider from your account settings first.');
    }

    public function errorCode(): string
    {
        return 'oauth_account_not_provisioned';
    }
}
