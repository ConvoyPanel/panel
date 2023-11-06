<?php

namespace Convoy\Facades;

use Illuminate\Support\Facades\Facade;
use Convoy\Services\Activity\ActivityLogTargetableService;

class LogTarget extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogTargetableService::class;
    }
}
