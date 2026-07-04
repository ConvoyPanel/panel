<?php

namespace App\Exceptions\Service\Deployment;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidTemplateException extends BadRequestHttpException implements HasErrorCode
{
    public function errorCode(): string
    {
        return 'invalid_template';
    }
}
