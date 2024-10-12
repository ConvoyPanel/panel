<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'version' => 'canary',

    'aliases' => Facade::defaultAliases()->merge([
        // Custom Facades
        'Activity' => Convoy\Facades\Activity::class,
        'LogBatch' => Convoy\Facades\LogBatch::class,
        'LogTarget' => Convoy\Facades\LogTarget::class,
    ])->toArray(),

];
