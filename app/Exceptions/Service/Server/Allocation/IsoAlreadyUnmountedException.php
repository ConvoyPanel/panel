<?php

namespace Convoy\Exceptions\Service\Server\Allocation;

use Convoy\Exceptions\DisplayException;

class IsoAlreadyUnmountedException extends DisplayException
{
    public function __construct()
    {
        parent::__construct('The requested ISO is already unmounted.');
    }
}
