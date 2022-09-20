<?php

namespace Convoy\Facades;

use Illuminate\Support\Facades\Facade;
use Convoy\Services\Activity\ActivityLogBatchService;

class LogBatch extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogBatchService::class;
    }
}
