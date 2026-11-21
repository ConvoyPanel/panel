<?php

namespace App\Http\Controllers\Admin;

use App\Data\PaginationMeta;
use App\Data\Webhook\WebhookData;
use App\Data\Webhook\WebhookDeliveryData;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Http\Requests\Admin\Webhooks\WebhookRequest;
use App\Models\User;
use App\Models\Webhook;
use App\Services\Webhooks\WebhookDispatcher;
use App\Support\Webhooks\WebhookEvents;
use Illuminate\Http\Request;

class WebhookController
{
    public function __construct(private WebhookDispatcher $dispatcher) {}

    /** Every event name a webhook can subscribe to. */
    public function events()
    {
        return ['data' => WebhookEvents::all()];
    }

    public function index(Request $request)
    {
        $webhooks = Webhook::query()
            ->with('latestDelivery')
            ->oldest('id')
            ->paginate(min($request->integer('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($webhooks, WebhookData::class);
    }

    public function store(WebhookRequest $request)
    {
        $webhook = Webhook::create([
            'url' => $request->string('url')->toString(),
            'description' => $request->input('description'),
            'events' => array_values($request->input('events')),
            'enabled' => $request->boolean('enabled', true),
            'secret' => Webhook::generateSecret(),
            'created_by' => $request->user() instanceof User ? $request->user()->id : null,
        ]);

        Audit::record(
            AuditEvent::ADMIN_WEBHOOK_CREATED,
            subject: $webhook,
            properties: ['url' => $webhook->url, 'events' => $webhook->events],
        );

        return WebhookData::withSecret($webhook);
    }

    public function show(Webhook $webhook)
    {
        return WebhookData::from($webhook->load('latestDelivery'));
    }

    public function update(WebhookRequest $request, Webhook $webhook)
    {
        $webhook->fill($request->safe()->only(['url', 'description', 'events', 'enabled']));

        $rotated = $request->boolean('rotate_secret');

        if ($rotated) {
            $webhook->secret = Webhook::generateSecret();
        }

        $webhook->save();

        Audit::record(
            AuditEvent::ADMIN_WEBHOOK_UPDATED,
            subject: $webhook,
            properties: array_filter([
                'changed' => array_values(array_diff(array_keys($webhook->getChanges()), ['updated_at', 'secret'])),
                'secret_rotated' => $rotated ?: null,
            ], fn ($value) => $value !== null),
        );

        $webhook->load('latestDelivery');

        return $rotated ? WebhookData::withSecret($webhook) : WebhookData::from($webhook);
    }

    public function destroy(Webhook $webhook)
    {
        Audit::record(
            AuditEvent::ADMIN_WEBHOOK_DELETED,
            subject: $webhook,
            properties: ['url' => $webhook->url],
        );

        $webhook->delete();

        return response()->noContent();
    }

    /** Sends a `webhook.ping` to this webhook alone, whatever it subscribes to. */
    public function test(Webhook $webhook)
    {
        $delivery = $this->dispatcher->dispatch(
            WebhookEvents::PING,
            ['webhook' => ['uuid' => $webhook->uuid]],
            only: $webhook,
        )->sole();

        Audit::record(AuditEvent::ADMIN_WEBHOOK_TESTED, subject: $webhook);

        return WebhookDeliveryData::from($delivery->fresh());
    }
}
