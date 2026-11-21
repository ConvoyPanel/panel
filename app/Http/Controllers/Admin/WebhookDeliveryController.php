<?php

namespace App\Http\Controllers\Admin;

use App\Data\PaginationMeta;
use App\Data\Webhook\WebhookDeliveryData;
use App\Enums\Webhook\WebhookDeliveryStatus;
use App\Jobs\Webhooks\DeliverWebhookJob;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WebhookDeliveryController
{
    public function index(Request $request, Webhook $webhook)
    {
        $deliveries = QueryBuilder::for($webhook->deliveries())
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('event'),
            ])
            ->defaultSort('-id')
            ->paginate(min($request->integer('per_page', 25), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($deliveries, WebhookDeliveryData::class);
    }

    /**
     * Sends the same delivery again: same id and body, freshly signed. A receiver that
     * de-duplicates on the id is safe to point this at.
     */
    public function redeliver(Webhook $webhook, WebhookDelivery $delivery)
    {
        $delivery->update([
            'status' => WebhookDeliveryStatus::PENDING,
            'error' => null,
        ]);

        DeliverWebhookJob::dispatch($delivery);

        return WebhookDeliveryData::from($delivery->fresh());
    }
}
