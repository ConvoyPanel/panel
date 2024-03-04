<?php

namespace Convoy\Exceptions\Service\Node\IsoLibrary;

use Convoy\Exceptions\DisplayException;

class InvalidIsoLinkException extends DisplayException
{
    public function __construct()
    {
        parent::__construct('The ISO link provided is either unreachable or invalid.');
    }
}
