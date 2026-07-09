<?php

namespace App\Services\Metrics;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin client for VictoriaMetrics — a single-binary, Prometheus-compatible time-series store.
 * Used to persist admin-overview scalars over time so the dashboard can show week-over-week deltas
 * and KPI sparklines. Every call is best-effort: if VM is unset or unreachable the dashboard simply
 * loses its trend context rather than erroring, so VM is never a hard dependency.
 */
class VictoriaMetrics
{
    private ?string $baseUrl;

    public function __construct(Repository $config)
    {
        $url = $config->get('metrics.victoriametrics.url');
        $this->baseUrl = is_string($url) && $url !== '' ? rtrim($url, '/') : null;
    }

    public function enabled(): bool
    {
        return $this->baseUrl !== null;
    }

    /**
     * Persist a batch of gauge samples at the current time via the Prometheus import endpoint.
     *
     * @param  array<string, int|float>  $metrics  metric name => value
     */
    public function writeNow(array $metrics): void
    {
        if (! $this->enabled() || $metrics === []) {
            return;
        }

        $timestampMs = (int) (now()->getTimestamp() * 1000);
        $body = '';
        foreach ($metrics as $name => $value) {
            $body .= "{$name} {$value} {$timestampMs}\n";
        }

        try {
            Http::timeout(5)
                ->withBody($body, 'text/plain')
                ->post("{$this->baseUrl}/api/v1/import/prometheus")
                ->throw();
        } catch (Throwable $e) {
            Log::warning('VictoriaMetrics write failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Range query. Returns each series' points keyed by metric name, oldest first.
     *
     * @return array<string, array<int, array{0: int, 1: float}>>
     */
    public function queryRange(string $query, string $start, string $end, string $step): array
    {
        if (! $this->enabled()) {
            return [];
        }

        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/v1/query_range", [
                'query' => $query,
                'start' => $start,
                'end' => $end,
                'step' => $step,
            ]);

            $out = [];
            foreach ($response->json('data.result', []) as $series) {
                $name = $series['metric']['__name__'] ?? null;
                if ($name === null) {
                    continue;
                }
                $out[$name] = array_map(
                    fn (array $point): array => [(int) $point[0], (float) $point[1]],
                    $series['values'] ?? [],
                );
            }

            return $out;
        } catch (Throwable $e) {
            Log::warning('VictoriaMetrics query failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
