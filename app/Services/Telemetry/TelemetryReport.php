<?php

namespace Convoy\Services\Telemetry;

/** A built report, and the view counts in it, which come off the counters once it is accepted. */
final readonly class TelemetryReport
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $views
     */
    public function __construct(
        public array $payload,
        public array $views,
    ) {}
}
