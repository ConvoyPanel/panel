<?php

namespace Convoy\Facades;

use Convoy\Services\Activity\ActivityRunnerService;
use Illuminate\Support\Facades\Facade;

class LogRunner extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityRunnerService::class;
    }
}
