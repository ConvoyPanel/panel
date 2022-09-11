<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Services\Activity\ActivityRunnerService;

class LogRunner extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityRunnerService::class;
    }
}
