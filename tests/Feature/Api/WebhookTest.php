<?php

use App\Enums\Admin\AdminPermission;
use App\Enums\Audit\AuditEvent;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Webhook\WebhookDeliveryStatus;
use App\Facades\Audit;
use App\Jobs\Webhooks\DeliverWebhookJob;
use App\Models\Backup;
use App\Models\Location;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\Api\CreateApplicationTokenService;
use App\Services\Webhooks\WebhookSigner;
use App\Support\Webhooks\WebhookEvents;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function webhook(array $events = ['*'], array $attributes = []): Webhook
{
    return Webhook::create([
        'url' => 'https://hooks.example.test/convoy',
        'events' => $events,
        'secret' => Webhook::generateSecret(),
        ...$attributes,
    ]);
}

/** The suite fakes the queue, so a delivery is run by hand where a test needs it sent. */
function deliverNow(WebhookDelivery $delivery): void
{
    app()->call([new DeliverWebhookJob($delivery), 'handle']);
}

describe('subscriptions', function () {
    it('queues a delivery for an audit event a webhook subscribes to', function () {
        $hook = webhook(['admin.location.*']);
        $location = Location::factory()->create();

        Audit::record(AuditEvent::ADMIN_LOCATION_CREATED, subject: $location);

        $delivery = WebhookDelivery::query()->sole();
        expect($delivery->webhook_id)->toBe($hook->id)
            ->and($delivery->event)->toBe('admin.location.created')
            ->and($delivery->payload['id'])->toBe($delivery->uuid)
            ->and($delivery->payload['data']['subject']['id'])->toBe($location->id);

        Queue::assertPushed(DeliverWebhookJob::class);
    });

    it('ignores events nobody subscribed to, and disabled webhooks', function () {
        webhook(['server.backup.completed']);
        webhook(['*'], ['enabled' => false]);

        Audit::record(AuditEvent::ADMIN_LOCATION_CREATED, subject: Location::factory()->create());

        expect(WebhookDelivery::query()->count())->toBe(0);
        Queue::assertNotPushed(DeliverWebhookJob::class);
    });

    it('sends nothing when the action rolls back', function () {
        webhook();

        try {
            DB::transaction(function () {
                Audit::record(AuditEvent::ADMIN_LOCATION_CREATED, subject: Location::factory()->create());

                throw new RuntimeException('rolled back');
            });
        } catch (RuntimeException) {
        }

        // The delivery row rolled back with the action, so the queued job has nothing to send.
        expect(WebhookDelivery::query()->count())->toBe(0);
    });

    it('reports a finished install', function () {
        webhook([WebhookEvents::SERVER_INSTALLATION_COMPLETED]);
        [, , , $server] = createServerModel();
        $server->update(['external_id' => 'whmcs-9']);
        $deployment = $server->deployments()->create([
            'type' => DeploymentType::INSTALL,
            'status' => DeploymentStatus::RUNNING,
            'start_on_completion' => false,
            'requested_at' => now(),
        ]);

        $deployment->update(['status' => DeploymentStatus::COMPLETED]);

        $delivery = WebhookDelivery::query()->sole();
        expect($delivery->event)->toBe('server.installation.completed')
            ->and($delivery->payload['data']['server']['externalId'])->toBe('whmcs-9')
            ->and($delivery->payload['data']['deployment']['type'])->toBe('install');
    });

    it('reports a failed backup once', function () {
        webhook(['server.backup.*']);
        [, , , $server] = createServerModel();
        $backup = Backup::factory()->for($server)->create(['completed_at' => null]);

        $backup->update(['completed_at' => now(), 'error_code' => 'timeout', 'error_message' => 'took too long']);
        $backup->update(['name' => 'renamed']);

        $delivery = WebhookDelivery::query()->sole();
        expect($delivery->event)->toBe('server.backup.failed')
            ->and($delivery->payload['data']['backup']['errorCode'])->toBe('timeout');
    });
});

describe('delivery', function () {
    it('signs the body with the webhook secret', function () {
        Http::fake(['hooks.example.test/*' => Http::response('ok')]);
        $hook = webhook();

        Audit::record(AuditEvent::ADMIN_LOCATION_CREATED, subject: Location::factory()->create());
        deliverNow(WebhookDelivery::query()->sole());

        $delivery = WebhookDelivery::query()->sole()->fresh();
        expect($delivery->status)->toBe(WebhookDeliveryStatus::SUCCEEDED)
            ->and($delivery->attempts)->toBe(1)
            ->and($delivery->response_status)->toBe(200);

        Http::assertSent(function (ClientRequest $request) use ($hook, $delivery) {
            preg_match('/^t=(\d+),v1=([0-9a-f]{64})$/', $request->header('Convoy-Signature')[0], $parts);
            $expected = app(WebhookSigner::class)->sign($hook->secret, (int) $parts[1], $request->body());

            return $request->header('Convoy-Event')[0] === 'admin.location.created'
                && $request->header('Convoy-Delivery')[0] === $delivery->uuid
                && hash_equals($expected, $parts[2])
                && $request['id'] === $delivery->uuid;
        });
    });

    it('records a receiver that refused and leaves it to retry', function () {
        Http::fake(['hooks.example.test/*' => Http::response('nope', 500)]);
        webhook();

        Audit::record(AuditEvent::ADMIN_LOCATION_CREATED, subject: Location::factory()->create());
        deliverNow(WebhookDelivery::query()->sole());

        $delivery = WebhookDelivery::query()->sole()->fresh();
        expect($delivery->status)->toBe(WebhookDeliveryStatus::PENDING)
            ->and($delivery->response_status)->toBe(500)
            ->and($delivery->error)->toBe('The receiver answered 500.');
    });
});

describe('the admin api', function () {
    beforeEach(function () {
        $admin = User::factory()->create(['root_admin' => true]);
        $this->token = app(CreateApplicationTokenService::class)->handle($admin, 'iac')->plainTextToken;
    });

    it('creates a webhook and shows its secret once', function () {
        $created = $this->withToken($this->token)
            ->postJson('/api/application/webhooks', [
                'url' => 'https://billing.example.test/hooks',
                'events' => ['server.*', 'admin.user.created'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.enabled', true);

        expect($created->json('data.secret'))->toStartWith('whsec_');

        $this->withToken($this->token)
            ->getJson('/api/application/webhooks/'.$created->json('data.uuid'))
            ->assertOk()
            ->assertJsonMissingPath('data.secret');
    });

    it('rejects an event that does not exist', function () {
        $this->withToken($this->token)
            ->postJson('/api/application/webhooks', [
                'url' => 'https://billing.example.test/hooks',
                'events' => ['server.exploded'],
            ])
            ->assertJsonValidationErrorFor('events.0');
    });

    it('rotates the secret', function () {
        $hook = webhook();
        $old = $hook->secret;

        $response = $this->withToken($this->token)
            ->putJson("/api/application/webhooks/{$hook->uuid}", ['rotate_secret' => true])
            ->assertOk();

        expect($response->json('data.secret'))->not->toBe($old)
            ->and($hook->fresh()->secret)->toBe($response->json('data.secret'));
    });

    it('sends a test event to one webhook', function () {
        $hook = webhook(['server.backup.completed']);

        $this->withToken($this->token)
            ->postJson("/api/application/webhooks/{$hook->uuid}/test")
            ->assertCreated()
            ->assertJsonPath('data.event', 'webhook.ping');

        // The test call itself is audited, which the webhook does not subscribe to.
        expect($hook->deliveries()->count())->toBe(1);
    });

    it('lists and redelivers deliveries', function () {
        $hook = webhook();
        $delivery = $hook->deliveries()->create([
            'event' => 'webhook.ping',
            'payload' => ['id' => 'x'],
            'status' => WebhookDeliveryStatus::FAILED,
        ]);

        $this->withToken($this->token)
            ->getJson("/api/application/webhooks/{$hook->uuid}/deliveries")
            ->assertOk()
            ->assertJsonPath('items.0.uuid', $delivery->uuid);

        $this->withToken($this->token)
            ->postJson("/api/application/webhooks/{$hook->uuid}/deliveries/{$delivery->uuid}/redeliver")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'pending');

        Queue::assertPushed(DeliverWebhookJob::class);
    });

    it('does not redeliver through a webhook that does not own the delivery', function () {
        $hook = webhook();
        $other = webhook();
        $delivery = $other->deliveries()->create([
            'event' => 'webhook.ping',
            'payload' => [],
            'status' => WebhookDeliveryStatus::FAILED,
        ]);

        $this->withToken($this->token)
            ->postJson("/api/application/webhooks/{$hook->uuid}/deliveries/{$delivery->uuid}/redeliver")
            ->assertNotFound();
    });

    it('keeps webhooks to the settings permission', function () {
        $this->actingAs(operator([AdminPermission::SERVERS_MANAGE]))
            ->getJson('/api/admin/webhooks')
            ->assertForbidden();

        $this->app['auth']->forgetGuards();

        $this->actingAs(operator([AdminPermission::SETTINGS_READ]))
            ->getJson('/api/admin/webhooks')
            ->assertOk();
    });
});
