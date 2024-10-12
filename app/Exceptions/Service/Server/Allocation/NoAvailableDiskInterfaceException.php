<?php

namespace App\Exceptions\Service\Server\Allocation;

use App\Exceptions\DisplayException;

class NoAvailableDiskInterfaceException extends DisplayException
{
    public function __construct()
    {
        parent::__construct(
            'There is no available disk interface on the virtual machine to satisfy the request.',
        );
    }
}
