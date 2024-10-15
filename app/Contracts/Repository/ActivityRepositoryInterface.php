<?php

namespace App\Contracts\Repository;

use App\Models\ActivityLog;
use App\Models\Server;

interface ActivityRepositoryInterface extends RepositoryInterface
{
    /**
     * Return the server model associated with an activity
     */
    public function getServer(ActivityLog $activity): ?Server;
}
