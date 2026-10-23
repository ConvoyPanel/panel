<?php

namespace Convoy\Services\Telemetry;

use Convoy\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Whether this panel reports to the Convoy console, under which id, and the two
 * requests it makes: the daily report and, once turned off, the request to erase
 * what the console holds for it.
 *
 * Telemetry is on unless one of three things turns it off:
 *
 * - CONVOY_TELEMETRY_ENABLED=false. The next daily run erases the
 *   installation's reports on the console instead of sending one.
 * - `c:telemetry:disable`, which erases them straight away.
 * - An empty CONVOY_TELEMETRY_URL. The panel then never contacts the console,
 *   so nothing is erased either; there is nowhere to send the request.
 */
class TelemetryService
{
    /** A random UUID, made on the first report. It is never derived from APP_KEY or APP_URL. */
    public const INSTALLATION = 'telemetry:installation';

    /** Set by `c:telemetry:disable`, cleared by `c:telemetry:enable`. */
    public const DISABLED = 'telemetry:disabled_at';

    private const TIMEOUT_SECONDS = 10;

    public function url(): string
    {
        return rtrim((string) config('convoy.telemetry.url'), '/');
    }

    public function enabled(): bool
    {
        return (bool) config('convoy.telemetry.enabled')
            && $this->url() !== ''
            && is_null(Setting::get(self::DISABLED));
    }

    public function installation(): ?string
    {
        return Setting::get(self::INSTALLATION);
    }

    public function installationOrCreate(): string
    {
        if ($installation = $this->installation()) {
            return $installation;
        }

        Setting::put(self::INSTALLATION, $installation = Str::uuid()->toString());

        return $installation;
    }

    /**
     * When the daily job runs, or null when it has nothing to do. The minute is
     * taken from the installation id, so panels spread out across the day rather
     * than all reporting at midnight.
     */
    public function reportTime(): ?string
    {
        if ($this->enabled()) {
            $installation = $this->installationOrCreate();
        } elseif ($this->url() !== '') {
            // Turned off but not yet erased on the console.
            $installation = $this->installation();
        }

        if (empty($installation)) {
            return null;
        }

        $minute = hexdec(substr(str_replace('-', '', $installation), 0, 8)) % 1440;

        return sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
    }

    /**
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function send(array $report): void
    {
        $this->request()->post($this->url(), $report)->throw();
    }

    /**
     * Asks the console to erase this installation's reports and drops the id, so
     * a panel that turns telemetry back on starts over as a new installation. The
     * id is kept until the console confirms, and the daily job tries again until
     * it does.
     *
     * @return bool Whether there was anything to erase
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function erase(): bool
    {
        $installation = $this->installation();

        if (is_null($installation) || $this->url() === '') {
            return false;
        }

        $this->request()->delete("{$this->url()}/{$installation}")->throw();

        Setting::forget(self::INSTALLATION);

        return true;
    }

    public function disable(): void
    {
        Setting::put(self::DISABLED, now()->toIso8601String());
    }

    public function enable(): void
    {
        Setting::forget(self::DISABLED);
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withUserAgent('Convoy/'.config('app.version'))
            ->timeout(self::TIMEOUT_SECONDS);
    }
}
