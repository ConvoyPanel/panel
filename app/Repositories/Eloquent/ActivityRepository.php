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
        /** @var \App\Models\ActivityLogSubject|null $activitySubject */
        $activitySubject = $activity->subjects()->firstWhere('subject_type', (new Server)->getMorphClass());
        $subject = $activitySubject?->subject()->first();

        return $subject instanceof Server ? $subject : null;
    }
}
