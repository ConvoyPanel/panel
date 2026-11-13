<?php

namespace Convoy\Jobs\Telemetry;

use Convoy\Services\Telemetry\TelemetryClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Asks the console to erase an installation's reports after an admin turns
 * telemetry off. Retried, unlike the report: an operator who opted out is owed
 * the erase even when the console is briefly unreachable.
 */
class EraseTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public function __construct(public string $installation)
    {
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 600, 3600, 21600];
    }

    public function handle(TelemetryClient $client): void
    {
        if (blank(config('convoy.telemetry.url'))) {
            return;
        }

        $client->erase($this->installation)->throw();
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('The console could not be asked to erase this install\'s telemetry reports.', [
            'installation' => $this->installation,
            'error' => $exception?->getMessage(),
        ]);
    }
}
