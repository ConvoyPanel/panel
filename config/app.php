<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'version' => 'canary',

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        Convoy\Providers\ActivityLogServiceProvider::class,
        Convoy\Providers\AppServiceProvider::class,
        Convoy\Providers\AuthServiceProvider::class,
        Convoy\Providers\BroadcastServiceProvider::class,
        Convoy\Providers\EventServiceProvider::class,
        Convoy\Providers\HorizonServiceProvider::class,
        Convoy\Providers\RouteServiceProvider::class,
        Convoy\Providers\RepositoryServiceProvider::class,
        Convoy\Providers\FortifyServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        // Custom Facades
        'Activity' => Convoy\Facades\Activity::class,
        'LogBatch' => Convoy\Facades\LogBatch::class,
        'LogTarget' => Convoy\Facades\LogTarget::class,
    ])->toArray(),

];
