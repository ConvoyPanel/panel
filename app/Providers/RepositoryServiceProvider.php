<?php

namespace Convoy\Providers;

use Convoy\Contracts\Repository\ActivityRepositoryInterface;
use Convoy\Repositories\Eloquent\ActivityRepository;
use Illuminate\Support\ServiceProvider;

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
