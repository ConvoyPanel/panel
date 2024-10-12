<?php

namespace Convoy\Facades;

use Convoy\Services\Activity\ActivityLogBatchService;
use Illuminate\Support\Facades\Facade;

class LogBatch extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ActivityLogBatchService::class;
    }
}
