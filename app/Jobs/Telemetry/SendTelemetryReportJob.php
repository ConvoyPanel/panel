<?php

namespace Convoy\Jobs\Telemetry;

use Carbon\CarbonImmutable;
use Convoy\Services\Telemetry\TelemetryClient;
use Convoy\Services\Telemetry\TelemetryReportService;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

/**
 * Sends the day's report (docs/telemetry.md). Scheduled once a day at this
 * install's own minute.
 *
 * `tries = 1`: a report that fails is not retried. The console keeps one report
 * per day and the page views stay on the counters until a report carrying them
 * is accepted, so waiting for tomorrow loses nothing but that day's snapshot.
 */
class SendTelemetryReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    /** @return list<object> */
    public function middleware(): array
    {
        // A second copy (two schedulers, a manual run) is dropped rather than released: the first
        // one is already sending today's report.
        return [(new WithoutOverlapping('telemetry-report'))->dontRelease()->expireAfter($this->timeout)];
    }

    public function handle(
        TelemetryService $telemetry,
        TelemetryReportService $reports,
        TelemetryClient $client,
    ): void {
        $now = CarbonImmutable::now();

        if (! $telemetry->enabled() || $telemetry->reportedOn($now)) {
            return;
        }

        $report = $reports->build($telemetry->installationId());

        try {
            $response = $client->send($report);
        } catch (ConnectionException $exception) {
            Log::warning('The telemetry report could not reach the Convoy console. The next one is due tomorrow.', [
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if (! $response->successful()) {
            Log::warning('The Convoy console did not accept the telemetry report. The next one is due tomorrow.', [
                'status' => $response->status(),
                'errors' => $response->json('errors'),
            ]);

            return;
        }

        $telemetry->markReported($report, $now);
    }
}
