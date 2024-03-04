<?php

namespace Convoy\Exceptions\Service\Server\Allocation;

use Convoy\Exceptions\DisplayException;

class NoAvailableDiskInterfaceException extends DisplayException
{
    public function __construct()
    {
        parent::__construct(
            'There is no available disk interface on the virtual machine to satisfy the request.',
        );
    }
}
