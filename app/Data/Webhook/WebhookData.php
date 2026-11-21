<?php

namespace App\Data\Webhook;

use App\Models\Webhook;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class WebhookData extends Data
{
    public function __construct(
        public string $uuid,
        public ?string $description,
        public string $url,
        /** @var list<string> */
        public array $events,
        public bool $enabled,
        public ?WebhookDeliveryData $latestDelivery,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        /** Present once, on the response that created or rotated it. */
        public string|Optional $secret = new Optional,
    ) {}

    public static function fromModel(Webhook $webhook): self
    {
        return new self(
            uuid: $webhook->uuid,
            description: $webhook->description,
            url: $webhook->url,
            events: $webhook->events ?? [],
            enabled: $webhook->enabled,
            latestDelivery: $webhook->relationLoaded('latestDelivery') && $webhook->latestDelivery !== null
                ? WebhookDeliveryData::fromModel($webhook->latestDelivery)
                : null,
            createdAt: $webhook->created_at,
            updatedAt: $webhook->updated_at,
        );
    }

    public static function withSecret(Webhook $webhook): self
    {
        $data = self::fromModel($webhook);
        $data->secret = $webhook->secret;

        return $data;
    }
}
