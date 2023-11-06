<?php

namespace Convoy\Repositories\Eloquent;

use Convoy\Models\Server;
use Convoy\Models\ActivityLog;
use Convoy\Contracts\Repository\ActivityRepositoryInterface;

class ActivityRepository extends EloquentRepository implements ActivityRepositoryInterface
{
    public function model(): string
    {
        return ActivityLog::class;
    }

    public function getServer(ActivityLog $activity): ?Server
    {
        return $activity->subjects()->firstWhere('subject_type', (new Server)->getMorphClass())?->subject()->first();
    }
}
