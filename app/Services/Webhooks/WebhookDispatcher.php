<?php

namespace App\Services\Webhooks;

use App\Data\Audit\AuditLogData;
use App\Enums\Webhook\WebhookDeliveryStatus;
use App\Jobs\Webhooks\DeliverWebhookJob;
use App\Models\AuditLog;
use App\Models\Server;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Support\Webhooks\WebhookEvents;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Turns an event into one queued delivery per webhook subscribed to it.
 *
 * Deliveries are written in whatever transaction the event happened in and queued after it
 * commits, so an action that rolls back sends nothing, and the receiver never hears about a
 * server the panel then failed to save.
 */
class WebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, WebhookDelivery>
     */
    public function dispatch(string $event, array $data, ?Webhook $only = null): Collection
    {
        $webhooks = $only !== null ? collect([$only]) : $this->subscribers($event);

        return $webhooks->map(function (Webhook $webhook) use ($event, $data) {
            $delivery = $webhook->deliveries()->create([
                'event' => $event,
                'payload' => [],
                'status' => WebhookDeliveryStatus::PENDING,
            ]);

            // Written after the row exists so the body carries the delivery's own id, the one
            // a receiver de-duplicates on.
            $delivery->update(['payload' => [
                'id' => $delivery->uuid,
                'event' => $event,
                'occurredAt' => now()->toIso8601ZuluString(),
                'data' => $data,
            ]]);

            DeliverWebhookJob::dispatch($delivery)->afterCommit();

            return $delivery;
        });
    }

    /** An audit entry, as the audit API shows it to an administrator. */
    public function auditRecorded(AuditLog $log): void
    {
        if ($this->subscribers($log->event->value)->isEmpty()) {
            return;
        }

        $this->dispatch($log->event->value, AuditLogData::forStaff($log)->toArray());
    }

    /**
     * What a webhook says about a server: enough for a receiver to find it again on its own side
     * without calling back, external id included.
     *
     * @return array<string, mixed>
     */
    public static function server(Server $server): array
    {
        return [
            'id' => $server->id,
            'uuid' => $server->uuid,
            'externalId' => $server->external_id,
            'name' => $server->name,
            'hostname' => $server->hostname,
            'userId' => $server->user_id,
            'nodeId' => $server->node_id,
            'lifecycle' => $server->lifecycle->value,
            'suspended' => $server->isSuspended(),
        ];
    }

    /**
     * Every audit entry asks this, so the enabled webhooks' subscriptions are cached and the
     * full rows are only loaded for an event somebody wants.
     *
     * @return Collection<int, Webhook>
     */
    private function subscribers(string $event): Collection
    {
        /** @var array<int, list<string>> $subscriptions */
        $subscriptions = Cache::rememberForever(Webhook::CACHE_KEY, fn () => Webhook::query()
            ->where('enabled', true)
            ->pluck('events', 'id')
            ->map(fn ($events) => is_array($events) ? $events : (json_decode((string) $events, true) ?? []))
            ->all());

        $ids = collect($subscriptions)
            ->filter(fn (array $events) => WebhookEvents::matches($events, $event))
            ->keys();

        return $ids->isEmpty() ? collect() : Webhook::query()->whereKey($ids)->get();
    }
}
