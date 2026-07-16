<?php

use App\Providers\ActivityLogServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;

return [
    ActivityLogServiceProvider::class,
    AppServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
    HorizonServiceProvider::class,
    FortifyServiceProvider::class,
];
