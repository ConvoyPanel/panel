<?php

namespace Convoy\Exceptions\Service\Server\Allocation;

use Convoy\Exceptions\ConvoyException;

class NoAvailableDiskInterfaceException extends ConvoyException
{
    public function __construct()
    {
        parent::__construct('There is no available disk interface on the virtual machine to satisfy the request.');
    }
}