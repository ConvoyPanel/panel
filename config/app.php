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
        App\Providers\ActivityLogServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\HorizonServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\RepositoryServiceProvider::class,
        App\Providers\FortifyServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        // Custom Facades
        'Activity' => App\Facades\Activity::class,
        'LogBatch' => App\Facades\LogBatch::class,
        'LogTarget' => App\Facades\LogTarget::class,
    ])->toArray(),

];
