<?php

namespace Convoy\Facades;

use Illuminate\Support\Facades\Facade;
use Convoy\Services\Activity\ActivityRunnerService;

class LogRunner extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityRunnerService::class;
    }
}
