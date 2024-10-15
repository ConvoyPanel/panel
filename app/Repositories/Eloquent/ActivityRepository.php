<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repository\ActivityRepositoryInterface;
use App\Models\ActivityLog;
use App\Models\Server;

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
