<?php

namespace App\Services\Servers;

use App\Services\ProxmoxService;

class StatusService extends ProxmoxService
{
    public function fetchStatus()
    {
        return $this->instance()->status()->current()->get();
    }
}
