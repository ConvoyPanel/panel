<?php

namespace Convoy\Facades;

use Convoy\Services\Activity\ActivityLogTargetableService;
use Illuminate\Support\Facades\Facade;

class LogTarget extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogTargetableService::class;
    }
}
