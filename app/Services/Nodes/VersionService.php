<?php

namespace App\Services\Nodes;

use App\Services\ProxmoxService;

class VersionService extends ProxmoxService
{
    public function getDetails()
    {
        return $this->mainInstance()->nodes()->node($this->node->cluster)->version()->get();
    }
}
