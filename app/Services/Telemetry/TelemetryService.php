<?php

namespace Convoy\Services\Telemetry;

use Carbon\CarbonImmutable;
use Convoy\Jobs\Telemetry\EraseTelemetryJob;
use Convoy\Models\TelemetrySetting;
use Illuminate\Support\Str;

/**
 * Whether this panel reports, and the one place that changes it. Both the
 * environment and the admin switch have to allow it; the environment wins.
 */
class TelemetryService
{
    public function __construct(private ViewCounter $views) {}

    /** False when CONVOY_TELEMETRY_ENABLED is off or there is no URL to send to. */
    public function allowedByEnvironment(): bool
    {
        return (bool) config('convoy.telemetry.enabled') && filled(config('convoy.telemetry.url'));
    }

    /** The environment variable that turned telemetry off, or null when it allows it. */
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
        return $this->allowedByEnvironment()
            && (TelemetrySetting::current()->enabled ?? (bool) config('convoy.telemetry.default'));
    }

    public function installationId(): string
    {
        return TelemetrySetting::current()->installation_id;
    }

    public function enable(): void
    {
        TelemetrySetting::current()->update(['enabled' => true]);
    }

    /**
     * Stops reporting and asks the console to erase what it holds. The id is
     * replaced in the same save, so turning it back on later reports as a new
     * panel instead of resuming the one that was erased.
     */
    public function disable(): void
    {
        $settings = TelemetrySetting::current();
        $previous = $settings->installation_id;

        $settings->update([
            'enabled' => false,
            'installation_id' => (string) Str::uuid(),
            'attempted_at' => null,
            'reported_at' => null,
        ]);

        $this->views->clear();

        if (filled(config('convoy.telemetry.url'))) {
            EraseTelemetryJob::dispatch($previous);
        }
    }

    /**
     * Each panel reports from its own minute of the UTC day, taken from its
     * random id, so the console hears from panels spread over 24 hours
     * instead of all of them at midnight. From that minute on it is due until
     * a report has gone out that UTC day, so a panel that was down, or a
     * scheduler tick that slipped past the minute, still reports that day.
     */
    public function isDue(CarbonImmutable $now): bool
    {
        $now = $now->utc();

        return $this->enabled()
            && $now->hour * 60 + $now->minute >= $this->minuteOfDay()
            && ! $this->attemptedOn($now);
    }

    public function minuteOfDay(): int
    {
        // The first 32 bits of a random UUID are plenty of spread for 1,440 slots.
        return (int) (hexdec(substr(str_replace('-', '', $this->installationId()), 0, 8)) % 1440);
    }

    /** At most one report goes out per UTC day, accepted or not. */
    public function attemptedOn(CarbonImmutable $now): bool
    {
        return TelemetrySetting::current()->attempted_at?->utc()->isSameDay($now->utc()) ?? false;
    }

    public function markAttempted(CarbonImmutable $now): void
    {
        TelemetrySetting::current()->update(['attempted_at' => $now]);
    }

    /**
     * The console accepted the report: take the views it carried off the
     * counters.
     */
    public function markReported(TelemetryReport $report, CarbonImmutable $now): void
    {
        $installation = $report->payload['installation'];
        $settings = TelemetrySetting::current();

        // Turned off while the report was in flight. The console has just stored a report under
        // an id that was meant to be erased, so erase it again. The counters were already
        // cleared, and subtracting from them now would leave them negative.
        if (! $this->enabled() || $settings->installation_id !== $installation) {
            EraseTelemetryJob::dispatch($installation);

            return;
        }

        $settings->update(['reported_at' => $now]);

        $this->views->subtract($report->views);
    }
}
