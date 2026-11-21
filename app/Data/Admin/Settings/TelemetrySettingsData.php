<?php

namespace App\Data\Admin\Settings;

use Spatie\LaravelData\Data;

/** The telemetry switch, as the admin Settings screen shows it. */
class TelemetrySettingsData extends Data
{
    public function __construct(
        /** Whether reports go out: the admin switch, unless the environment turned it off. */
        public bool $enabled,
        /** The environment variable that turned telemetry off, or null when the switch decides. */
        public ?string $disabledBy,
    ) {}
}
