<?php

namespace Convoy\Jobs\Telemetry;

use Convoy\Services\Telemetry\TelemetryReportService;
use Convoy\Services\Telemetry\TelemetryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sends the day's telemetry report, or, once telemetry is turned off, asks the
 * console to erase this installation's reports.
 */
class SendTelemetryReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * A report that doesn't get through waits for the next day: the console
     * keeps one report per day, so nothing is lost but that day.
     */
    public int $tries = 1;

    public int $timeout = 120;

    public function handle(TelemetryService $telemetry, TelemetryReportService $reports): void
    {
        try {
            if ($telemetry->enabled()) {
                $telemetry->send($reports->build($telemetry->installationOrCreate()));
            } else {
                $telemetry->erase();
            }
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Could not reach the Convoy console for telemetry; trying again tomorrow.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
