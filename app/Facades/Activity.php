<?php

namespace Convoy\Facades;

use Illuminate\Support\Facades\Facade;
use Convoy\Services\Activity\ActivityLogService;

class Activity extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogService::class;
    }
}
