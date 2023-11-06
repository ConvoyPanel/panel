<?php

namespace Convoy\Contracts\Repository;

use Convoy\Models\Server;
use Convoy\Models\ActivityLog;

interface ActivityRepositoryInterface extends RepositoryInterface
{
    /**
     * Return the server model associated with an activity
     */
    public function getServer(ActivityLog $activity): ?Server;
}
