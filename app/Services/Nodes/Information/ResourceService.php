<?php

namespace App\Services\Nodes\Information;

use App\Services\ProxmoxService;

class ResourceService extends ProxmoxService
{
    public function getResourceList()
    {
        $data = $this->mainInstance()->cluster()->resources()->get();
        return $data ? $data['data'] : [];
    }
}
