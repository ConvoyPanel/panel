<?php

namespace Convoy\Exceptions\Service\Node\IsoLibrary;

use Convoy\Exceptions\ConvoyException;

class InvalidIsoLinkException extends ConvoyException
{
    public function __construct()
    {
        parent::__construct('The ISO link provided is either unreachable or invalid.');
    }
}
