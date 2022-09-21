<?php

namespace Convoy\Repositories\Eloquent;

use Convoy\Contracts\Repository\ActivityRepositoryInterface;
use Convoy\Models\ActivityLog;
use Convoy\Models\Server;

/**
 *
 */
class ActivityRepository extends EloquentRepository implements ActivityRepositoryInterface
{
    /**
     * @return string
     */
    public function model()
    {
        return ActivityLog::class;
    }

    /**
     * @param ActivityLog $activity
     * @return Server|null
     */
    public function getServer(ActivityLog $activity): ?Server
    {
        return $activity->subjects()->firstWhere('subject_type', (new Server)->getMorphClass())?->subject()->first();
    }

}