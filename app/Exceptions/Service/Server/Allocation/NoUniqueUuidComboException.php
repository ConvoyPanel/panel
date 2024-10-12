<?php

namespace App\Exceptions\Service\Server\Allocation;

use App\Exceptions\DisplayException;

class NoUniqueUuidComboException extends DisplayException
{
    public function __construct()
    {
        parent::__construct('There is no available VMID to use.');
    }
}
