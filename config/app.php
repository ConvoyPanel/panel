<?php

use App\Facades\Activity;
use App\Facades\LogBatch;
use App\Facades\LogTarget;
use Illuminate\Support\Facades\Facade;

return [
    'version' => 'canary',

    'aliases' => Facade::defaultAliases()->merge([
        // Custom Facades
        'Activity' => Activity::class,
        'LogBatch' => LogBatch::class,
        'LogTarget' => LogTarget::class,
    ])->toArray(),
];
