<?php

namespace Convoy\Facades;

use Convoy\Services\Activity\ActivityLogService;
use Illuminate\Support\Facades\Facade;

class Activity extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogService::class;
    }
}
