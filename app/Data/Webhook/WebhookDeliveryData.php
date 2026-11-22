<?php

namespace App\Data\Webhook;

use App\Enums\Webhook\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class WebhookDeliveryData extends Data
{
    public function __construct(
        public string $uuid,
        public string $event,
        public WebhookDeliveryStatus $status,
        public int $attempts,
        public ?int $responseStatus,
        public ?string $responseBody,
        public ?string $error,
        /** @var array<string, mixed> */
        public array $payload,
        public ?CarbonImmutable $deliveredAt,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromModel(WebhookDelivery $delivery): self
    {
        return new self(
            uuid: $delivery->uuid,
            event: $delivery->event,
            status: $delivery->status,
            attempts: $delivery->attempts,
            responseStatus: $delivery->response_status,
            responseBody: $delivery->response_body,
            error: $delivery->error,
            payload: $delivery->payload ?? [],
            deliveredAt: $delivery->delivered_at,
            createdAt: $delivery->created_at,
        );
    }
}
