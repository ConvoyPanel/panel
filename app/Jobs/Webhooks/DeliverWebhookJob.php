<?php

namespace App\Jobs\Webhooks;

use App\Enums\Webhook\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Services\Webhooks\WebhookSigner;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * POSTs one delivery, and tries again on a schedule when the receiver does not answer 2xx.
 *
 * Six attempts over about two and a half hours. A failure is recorded on the delivery rather
 * than thrown, so a receiver that is down does not fill the failed-jobs table, and the delivery
 * row is where an operator looks to see what went wrong.
 */
class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    public int $timeout = 30;

    /** A webhook deleted while this waited took its deliveries with it. */
    public bool $deleteWhenMissingModels = true;

    /** Seconds to wait before attempts 2 to 6. */
    private const BACKOFF = [10, 60, 300, 1800, 7200];

    public function __construct(public WebhookDelivery $delivery) {}

    public function handle(WebhookSigner $signer): void
    {
        $delivery = $this->delivery;
        $webhook = $delivery->webhook;

        // A webhook deleted while this waited takes its deliveries with it, which
        // $deleteWhenMissingModels handles. A redelivery of one that already went through
        // can still arrive here.
        if ($delivery->status === WebhookDeliveryStatus::SUCCEEDED) {
            return;
        }

        $body = json_encode($delivery->payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $delivery->attempts++;

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders($signer->headers($webhook, $delivery, $body))
                ->withBody($body, 'application/json')
                ->post($webhook->url);

            $delivery->response_status = $response->status();
            $delivery->response_body = Str::limit($response->body(), 2000);
            $delivery->error = $response->successful() ? null : "The receiver answered {$response->status()}.";

            if ($response->successful()) {
                $delivery->status = WebhookDeliveryStatus::SUCCEEDED;
                $delivery->delivered_at = CarbonImmutable::now();
                $delivery->save();

                return;
            }
        } catch (ConnectionException $exception) {
            $delivery->response_status = null;
            $delivery->response_body = null;
            $delivery->error = Str::limit($exception->getMessage(), 2000);
        }

        if ($this->attempts() >= $this->tries) {
            $delivery->status = WebhookDeliveryStatus::FAILED;
            $delivery->save();

            return;
        }

        $delivery->status = WebhookDeliveryStatus::PENDING;
        $delivery->save();

        $this->release(self::BACKOFF[min($this->attempts(), count(self::BACKOFF)) - 1]);
    }
}
