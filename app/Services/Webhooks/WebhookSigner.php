<?php

namespace App\Services\Webhooks;

use App\Models\Webhook;
use App\Models\WebhookDelivery;

/**
 * The headers a delivery is sent with, signature included.
 *
 * `Convoy-Signature: t=<unix time>,v1=<hex HMAC-SHA256 of "<t>.<raw body>">`, keyed with the
 * webhook's secret. A receiver recomputes the HMAC over the body it received and compares, and
 * rejects a `t` too far from its own clock so a captured request cannot be replayed later.
 */
class WebhookSigner
{
    /** @return array<string, string> */
    public function headers(Webhook $webhook, WebhookDelivery $delivery, string $body, ?int $timestamp = null): array
    {
        $timestamp ??= now()->getTimestamp();

        return [
            'User-Agent' => 'Convoy-Webhooks',
            'Convoy-Event' => $delivery->event,
            'Convoy-Delivery' => $delivery->uuid,
            'Convoy-Signature' => 't='.$timestamp.',v1='.$this->sign($webhook->secret, $timestamp, $body),
        ];
    }

    public function sign(string $secret, int $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }
}
