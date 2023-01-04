<?php

namespace Convoy\Exceptions\Service\Server\Allocation;

use Convoy\Exceptions\ConvoyException;

class NoAvailableBusException extends ConvoyException
{
    public function __construct()
    {
        parent::__construct('There is no available bus or device on the virtual machine to satisfy the request.');
    }
}