<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class TemplateService extends ProxmoxService
{
    public function createTemplate(array $params = [])
    {
        return $this->instance()->postTemplate($params);
    }
}
