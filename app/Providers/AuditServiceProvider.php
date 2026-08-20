<?php

namespace App\Providers;

use App\Listeners\AuditAuthenticationSubscriber;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Scoped, not a plain singleton: the logger holds the current batch's UUID and nesting depth,
     * which is per-request state and must not leak between requests on a long-lived worker.
     */
    public function register(): void
    {
        $this->app->scoped(AuditLogger::class);
    }

    /**
     * Registered explicitly: Laravel auto-discovers plain listeners, but not subscribers.
     */
    public function boot(): void
    {
        Event::subscribe(AuditAuthenticationSubscriber::class);
    }
}
