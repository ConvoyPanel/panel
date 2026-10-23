<?php

namespace App\Jobs\Telemetry;

use App\Jobs\Middleware\ExpiringWithoutOverlapping;
use App\Services\Telemetry\TelemetryClient;
use App\Services\Telemetry\TelemetryReportBuilder;
use App\Services\Telemetry\TelemetryService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends the day's report (docs/telemetry.md). Scheduled once a day at this
 * install's own minute.
 *
 * `tries = 1`: a report that fails is not retried. The next day's report covers
 * everything since the last one the console accepted, so nothing is lost by
 * waiting, and retrying could only add load on a console that is struggling.
 */
class SendTelemetryReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    /** @return list<object> */
    public function middleware(): array
    {
        // A second copy (two schedulers, a manual run) is dropped rather than released: the first
        // one is already sending today's report.
        return [(new ExpiringWithoutOverlapping('telemetry-report'))->dontRelease()];
    }

    public function handle(
        TelemetryService $telemetry,
        TelemetryReportBuilder $builder,
        TelemetryClient $client,
    ): void {
        $now = CarbonImmutable::now();

        if (! $telemetry->enabled() || $telemetry->reportedOn($now)) {
            return;
        }

        $report = $builder->build($now);

        try {
            $response = $client->send($report->payload);
        } catch (ConnectionException $e) {
            Log::warning('The telemetry report could not reach the console. The next one is due tomorrow.', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (! $response->successful()) {
            Log::warning('The console did not accept the telemetry report. The next one is due tomorrow.', [
                'status' => $response->status(),
                'errors' => $response->json('errors'),
            ]);

            return;
        }

        $telemetry->markReported($report, $now);
    }
}
