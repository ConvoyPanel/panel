<?php

namespace Convoy\Contracts\Repository;

use Convoy\Models\ActivityLog;
use Convoy\Models\Server;

interface ActivityRepositoryInterface extends RepositoryInterface
{
    /**
     * Return the server model associated with an activity
     */
    public function getServer(ActivityLog $activity): ?Server;
}
