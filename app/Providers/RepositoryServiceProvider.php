<?php

namespace Convoy\Providers;

use Illuminate\Support\ServiceProvider;
use Convoy\Repositories\Eloquent\ActivityRepository;
use Convoy\Contracts\Repository\ActivityRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register all of the repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(ActivityRepositoryInterface::class, ActivityRepository::class);
    }
}
