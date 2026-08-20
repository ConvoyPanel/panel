<?php

use App\Facades\Audit;
use Illuminate\Support\Facades\Facade;

return [
    'version' => 'canary',

    /*
     * Only the store is overridden here. Laravel defaults it to the `database`
     * cache store, which reads a `cache` table Convoy has no migration for, so
     * any install that selects the `cache` driver fails on every request with
     * "relation cache does not exist" -- Redis is the store Convoy actually has.
     *
     * The driver keeps Laravel's `file` default: file-based maintenance mode
     * only marks down the process that ran the command, which is correct for a
     * single-process install and wrong for a containerised one. Deployments that
     * run web, worker and scheduler separately set APP_MAINTENANCE_DRIVER=cache
     * so that `artisan down` takes all three out at once.
     */
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'redis'),
    ],

    'aliases' => Facade::defaultAliases()->merge([
        // Custom Facades
        'Audit' => Audit::class,
    ])->toArray(),
];
