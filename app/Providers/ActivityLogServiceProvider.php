<?php

namespace Convoy\Providers;

use Convoy\Services\Activity\ActivityLogBatchService;
use Convoy\Services\Activity\ActivityLogTargetableService;
use Illuminate\Support\ServiceProvider;

class ActivityLogServiceProvider extends ServiceProvider
{
    /**
     * Registers the necessary activity logger singletons scoped to the individual
     * request instances.
     */
    public function register(): void
    {
        $this->app->scoped(ActivityLogBatchService::class);
        $this->app->scoped(ActivityLogTargetableService::class);
    }
}
