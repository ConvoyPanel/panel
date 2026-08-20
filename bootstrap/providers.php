<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuditServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;

return [
    AuditServiceProvider::class,
    AppServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
    HorizonServiceProvider::class,
    FortifyServiceProvider::class,
];
