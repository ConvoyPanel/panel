<?php

namespace App\Exceptions\Service\Node\IsoLibrary;

use App\Exceptions\HasErrorCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidIsoLinkException extends BadRequestHttpException implements HasErrorCode
{
    public function __construct()
    {
        parent::__construct('The ISO link provided is either unreachable or invalid.');
    }

    public function errorCode(): string
    {
        return 'invalid_iso_link';
    }
}
