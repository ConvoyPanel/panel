<?php

namespace Convoy\Exceptions\Service\Server\Allocation;

use Convoy\Exceptions\ConvoyException;

class IsoAlreadyUnmountedException extends ConvoyException
{
    public function __construct()
    {
        parent::__construct('The requested ISO is already unmounted.');
    }
}