<?php

namespace App\Settings;

use App\Services\Telemetry\TelemetryService;
use Carbon\CarbonImmutable;
use Spatie\LaravelSettings\Settings;

/**
 * The anonymous daily report (docs/telemetry.md). Reads and writes go through
 * {@see TelemetryService}, which also folds in the environment's off switch.
 */
class TelemetrySettings extends Settings
{
    /**
     * The admin switch. On by default; turning it off erases this install's
     * reports on the console and replaces {@see $installation_id}.
     */
    public bool $enabled = true;

    /**
     * A random UUID that names this install to the console and nothing else.
     * Never derived from APP_KEY, APP_URL or a license, so the console cannot
     * tie a report back to a panel's address or owner.
     */
    public string $installation_id = '';

    // When the console last accepted a report, or null before the first one. The next report
    // counts audit events from here, and at most one report goes out per UTC day.
    //
    // Line comments rather than docblocks on both dates, for the reason given on
    // MailSettings::$encryption: a docblock without `@var` leaves the property with no cast.
    public ?CarbonImmutable $reported_at = null;

    // When this installation started counting: the upgrade that added telemetry, or the moment an
    // admin turned it back on. The first report counts audit events from here, so a report never
    // covers time when telemetry was off, and a first report that fails loses nothing.
    public ?CarbonImmutable $counting_since = null;

    public static function group(): string
    {
        return 'telemetry';
    }
}
