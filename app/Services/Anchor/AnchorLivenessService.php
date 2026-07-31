<?php

namespace App\Services\Anchor;

use App\Models\Anchor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Refreshes an Anchor's liveness by *pulling* `/api/v1/info`.
 *
 * Anchors normally push heartbeats to the panel, which is the right default:
 * an agent sits on a Proxmox node behind NAT and binds 127.0.0.1 by default,
 * so requiring the panel to reach it inbound would not work for most installs.
 *
 * This is a fallback for the inverse case — the panel can reach the Anchor's
 * public URL, but the Anchor's outbound heartbeat cannot reach the panel
 * (split networks, egress filtering, or a panel on an address the node cannot
 * resolve). `/api/v1/info` returns exactly the fields a heartbeat carries, so
 * a successful probe can stand in for one.
 *
 * Probing is deliberately on-demand rather than part of Anchor::compatibility(),
 * which is evaluated once per row when listing anchors and must stay pure.
 */
class AnchorLivenessService
{
    private const TIMEOUT_SECONDS = 5;

    /**
     * Probe the Anchor and record the result as a heartbeat.
     *
     * Returns true when the Anchor answered with a usable payload. Failure is
     * never fatal: the caller falls back to whatever the pushed heartbeat left
     * behind, so an unreachable Anchor simply stays offline.
     */
    public function refresh(Anchor $anchor): bool
    {
        $url = rtrim($anchor->public_url, '/').'/api/v1/info';

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get($url);
        } catch (\Throwable $exception) {
            Log::debug('Anchor liveness probe failed.', [
                'anchor' => $anchor->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        // `mode` is reported by the Anchor itself; a mismatch means this URL is
        // serving a different installation than the one we have on record, so
        // it must not count as this Anchor being alive. The heartbeat endpoint
        // enforces the same invariant.
        if ($response->json('mode') !== $anchor->mode->value) {
            return false;
        }

        $min = $response->json('protocol.min');
        $max = $response->json('protocol.max');

        if (! is_int($min) || ! is_int($max)) {
            return false;
        }

        $anchor->update([
            'last_seen_at' => now(),
            'version' => (string) $response->json('version'),
            'protocol_min' => $min,
            'protocol_max' => $max,
            'capabilities' => $response->json('capabilities') ?? [],
        ]);

        return true;
    }
}
