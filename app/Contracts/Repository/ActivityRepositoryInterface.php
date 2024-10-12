<?php

namespace App\Contracts\Repository;

use App\Models\Server;
use App\Models\ActivityLog;

interface ActivityRepositoryInterface extends RepositoryInterface
{
    /**
     * Return the server model associated with an activity
     */
    public function getServer(ActivityLog $activity): ?Server;
}
