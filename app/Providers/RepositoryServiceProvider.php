<?php

namespace App\Providers;

use App\Contracts\Repository\ActivityRepositoryInterface;
use App\Repositories\Eloquent\ActivityRepository;
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
