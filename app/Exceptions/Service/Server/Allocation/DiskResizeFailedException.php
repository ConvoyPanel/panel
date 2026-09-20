<?php

namespace Convoy\Exceptions\Service\Server\Allocation;

use Convoy\Exceptions\DisplayException;

class DiskResizeFailedException extends DisplayException
{
    public function __construct(string $reason)
    {
        parent::__construct("Proxmox could not resize the disk: {$reason}");
    }
}
