<?php

namespace App\Exceptions\Service\Server\Allocation;

use App\Exceptions\DisplayException;

class IsoAlreadyMountedException extends DisplayException
{
    public function __construct()
    {
        parent::__construct('The requested ISO is already mounted');
    }
}
