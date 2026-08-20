<?php

namespace App\Facades;

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * Named `Audit` rather than `AuditLog` so it does not collide with {@see AuditLog}, the model —
 * the two would otherwise need aliasing in every file that touches both.
 *
 * @method static AuditLog|null record(AuditEvent $event, ?Model $subject = null, array $properties = [], ?Model $actor = null)
 * @method static mixed batch(Closure $callback)
 *
 * @see AuditLogger
 */
class Audit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuditLogger::class;
    }
}
