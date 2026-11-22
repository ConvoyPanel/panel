<?php

namespace App\Services\Telemetry;

use App\Jobs\Telemetry\EraseTelemetryJob;
use App\Settings\TelemetrySettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Whether this install reports, and the one place that changes it. Both the
 * environment and the admin switch have to allow it; the environment wins.
 */
class TelemetryService
{
    public function __construct(
        private TelemetrySettings $settings,
        private ViewCounter $views,
    ) {}

    /** False when CONVOY_TELEMETRY_ENABLED is off or there is no URL to send to. */
    public function allowedByEnvironment(): bool
    {
        return (bool) config('convoy.telemetry.enabled') && filled(config('convoy.telemetry.url'));
    }

    public function enabled(): bool
    {
        return $this->allowedByEnvironment() && $this->settings->enabled;
    }

    public function installationId(): string
    {
        return $this->settings->installation_id;
    }

    public function enable(): void
    {
        $this->settings->enabled = true;
        $this->settings->counting_since = CarbonImmutable::now();
        $this->settings->save();
    }

    /**
     * Stops reporting and asks the console to erase what it holds. The id is
     * replaced in the same save, so a later opt-in reports as a new install
     * instead of resuming the one that was erased.
     */
    public function disable(): void
    {
        $previous = $this->settings->installation_id;

        $this->settings->enabled = false;
        $this->settings->installation_id = (string) Str::uuid();
        $this->settings->reported_at = null;
        $this->settings->counting_since = null;
        $this->settings->save();

        $this->views->clear();

        if (filled(config('convoy.telemetry.url'))) {
            EraseTelemetryJob::dispatch($previous);
        }
    }

    /**
     * Each install reports at its own minute of the UTC day, taken from its
     * random id, so the console hears from installs spread over 24 hours
     * instead of all of them at midnight.
     */
    public function isDue(CarbonImmutable $now): bool
    {
        $now = $now->utc();

        return $this->enabled() && $now->hour * 60 + $now->minute === $this->minuteOfDay();
    }

    public function minuteOfDay(): int
    {
        // The first 32 bits of a random UUID are plenty of spread for 1,440 slots.
        return (int) (hexdec(substr(str_replace('-', '', $this->settings->installation_id), 0, 8)) % 1440);
    }

    /** At most one report per UTC day: a second one would replace the first on the console. */
    public function reportedOn(CarbonImmutable $now): bool
    {
        return $this->settings->reported_at?->utc()->isSameDay($now->utc()) ?? false;
    }

    /**
     * Where the next report's activity window starts: the last accepted report,
     * or when counting started for the first one.
     */
    public function windowStart(CarbonImmutable $until): CarbonImmutable
    {
        return $this->settings->reported_at ?? $this->settings->counting_since ?? $until->subDay();
    }

    /**
     * The console accepted the report: move the activity window on and take
     * the views it carried off the counters.
     */
    public function markReported(TelemetryReport $report, CarbonImmutable $until): void
    {
        $installation = $report->payload['installation'];

        $this->settings->refresh();

        // Turned off while the report was in flight. The console has just stored a report under
        // an id that was meant to be erased, so erase it again. The counters were already
        // cleared, and subtracting from them now would leave them negative.
        if (! $this->settings->enabled || $this->settings->installation_id !== $installation) {
            EraseTelemetryJob::dispatch($installation);

            return;
        }

        $this->settings->reported_at = $until;
        $this->settings->save();

        $this->views->subtract($report->views);
    }
}
