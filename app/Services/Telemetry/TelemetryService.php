<?php

namespace Convoy\Services\Telemetry;

use Carbon\CarbonImmutable;
use Convoy\Jobs\Telemetry\EraseTelemetryJob;
use Convoy\Settings\TelemetrySettings;
use Illuminate\Support\Str;

/**
 * Whether this install reports, and the one place that changes it. Both the
 * environment and the admin switch have to allow it; the environment wins.
 *
 * - The admin switch (Settings → Telemetry, or `c:telemetry:disable`) stops the
 *   reports, asks the console to erase what it holds, and replaces the id.
 * - CONVOY_TELEMETRY_ENABLED=false or an empty CONVOY_TELEMETRY_URL stop the
 *   reports without contacting the console.
 */
class TelemetryService
{
    public function __construct(
        private TelemetrySettings $settings,
        private PageViewCounter $views,
    ) {
    }

    /** False when CONVOY_TELEMETRY_ENABLED is off or there is no URL to send to. */
    public function allowedByEnvironment(): bool
    {
        return (bool) config('convoy.telemetry.enabled') && filled(config('convoy.telemetry.url'));
    }

    /** The environment variable that keeps telemetry off, if one does. */
    public function disabledBy(): ?string
    {
        return match (true) {
            ! config('convoy.telemetry.enabled') => 'CONVOY_TELEMETRY_ENABLED',
            blank(config('convoy.telemetry.url')) => 'CONVOY_TELEMETRY_URL',
            default => null,
        };
    }

    public function enabled(): bool
    {
        return $this->allowedByEnvironment() && $this->settings->enabled;
    }

    /** The admin switch alone, whatever the environment says. */
    public function switchedOn(): bool
    {
        return $this->settings->enabled;
    }

    public function installationId(): string
    {
        return $this->settings->installation_id;
    }

    public function url(): string
    {
        return rtrim((string) config('convoy.telemetry.url'), '/');
    }

    public function enable(): void
    {
        $this->settings->enabled = true;
        $this->settings->counting_since = CarbonImmutable::now();
        $this->settings->save();
    }

    /**
     * Stops reporting and, when there is a console to ask, has it erase what it
     * holds. The id is replaced in the same save, so a later opt-in reports as a
     * new install instead of resuming the one that was erased.
     *
     * @return string|null The id the console was asked to erase
     */
    public function disable(): ?string
    {
        $previous = $this->settings->installation_id;

        $this->settings->enabled = false;
        $this->settings->installation_id = (string) Str::uuid();
        $this->settings->reported_at = null;
        $this->settings->counting_since = null;
        $this->settings->save();

        $this->views->clear();

        if (blank(config('convoy.telemetry.url'))) {
            return null;
        }

        EraseTelemetryJob::dispatch($previous);

        return $previous;
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

    /** The time of day the report goes out, in UTC ("23:10"). */
    public function reportTime(): string
    {
        $minute = $this->minuteOfDay();

        return sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
    }

    /** At most one report per UTC day: a second one would replace the first on the console. */
    public function reportedOn(CarbonImmutable $now): bool
    {
        return $this->settings->reported_at?->utc()->isSameDay($now->utc()) ?? false;
    }

    public function reportedAt(): ?CarbonImmutable
    {
        return $this->settings->reported_at;
    }

    /**
     * The console accepted the report: record the day and take the views it
     * carried off the counters.
     *
     * @param  array<string, mixed>  $report
     */
    public function markReported(array $report, CarbonImmutable $at): void
    {
        $installation = $report['installation'];

        $this->settings->refresh();

        // Turned off while the report was in flight. The console has just stored a report under
        // an id that was meant to be erased, so erase it again. The counters were already
        // cleared, and subtracting from them now would leave them negative.
        if (! $this->settings->enabled || $this->settings->installation_id !== $installation) {
            EraseTelemetryJob::dispatch($installation);

            return;
        }

        $this->settings->reported_at = $at;
        $this->settings->save();

        $this->views->subtract((array) $report['views']);
    }
}
