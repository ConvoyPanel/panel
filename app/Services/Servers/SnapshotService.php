<?php

namespace App\Services\Servers;

use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class SnapshotService extends ProxmoxService
{
    /**
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public function doSnapshot(string $name)
    {
        return $this->instance()->snapshot()->post(['snapname' => $name]);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function fetchSnapshots()
    {
        return $this->instance()->snapshot()->get();
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function rollbackSnapshot(string $snapname)
    {
        // return $this->instance($server, $cluster)->snapname($snapname)->postRollback();
        return $this->instance()->snapshot()->snapname($snapname)->rollback()->post();
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function deleteSnapshot(string $snapname)
    {
        return $this->instance()->snapshot()->snapname($snapname)->delete();
    }
}
