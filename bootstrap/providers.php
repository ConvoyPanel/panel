<?php

use App\Providers\ApiDocsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuditServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TypeScriptTransformerServiceProvider;
use App\Providers\WebhookServiceProvider;

return [
    AuditServiceProvider::class,
    AppServiceProvider::class,
    TypeScriptTransformerServiceProvider::class,
    HorizonServiceProvider::class,
    FortifyServiceProvider::class,
    WebhookServiceProvider::class,
    ApiDocsServiceProvider::class,
];
