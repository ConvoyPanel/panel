<?php

namespace App\Services\Servers;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class CreationService extends ProxmoxService
{
    /**
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public function createInstance(int $id, string $name)
    {
        return $this->instance()->clone()->post(['newid' => $id, 'name' => $name]);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function changeConfiguration(int $memory, int $storage, int $cores)
    {
        // Will be used to change VM info after clone
    }
}
