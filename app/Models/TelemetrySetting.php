<?php

namespace Convoy\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * The single row (id 1) behind the telemetry switch and the daily report
 * (docs/telemetry.md). Read and changed through TelemetryService.
 *
 * @property ?bool $enabled null until an admin uses the switch
 * @property string $installation_id
 * @property ?CarbonImmutable $attempted_at
 * @property ?CarbonImmutable $reported_at
 */
class TelemetrySetting extends EloquentModel
{
    protected $table = 'telemetry_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'enabled' => 'boolean',
        'attempted_at' => 'immutable_datetime',
        'reported_at' => 'immutable_datetime',
    ];

    public static function current(): self
    {
        $settings = static::query()->find(1);

        if ($settings !== null) {
            return $settings;
        }

        // The migration inserts the row; this only runs if it was deleted. The fixed id keeps two
        // requests doing it at once from leaving two rows.
        try {
            return static::query()->forceCreate(['id' => 1, 'installation_id' => (string) Str::uuid()]);
        } catch (UniqueConstraintViolationException) {
            return static::query()->findOrFail(1);
        }
    }
}
