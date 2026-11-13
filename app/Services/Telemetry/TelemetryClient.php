<?php

namespace Convoy\Services\Telemetry;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** The two calls the console takes: a day's report, and erasing an installation. */
class TelemetryClient
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    public function send(array $payload): Response
    {
        return $this->request()->post($this->url(), $payload);
    }

    /** @throws \Illuminate\Http\Client\ConnectionException */
    public function erase(string $installation): Response
    {
        return $this->request()->delete($this->url().'/'.rawurlencode($installation));
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withUserAgent('Convoy/'.config('app.version'))
            ->connectTimeout(5)
            ->timeout(10);
    }

    private function url(): string
    {
        return rtrim((string) config('convoy.telemetry.url'), '/');
    }
}
