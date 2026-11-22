<?php

use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\PersonalAccessToken;
use App\Models\SystemActor;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\Api\CreateAccountTokenService;
use App\Services\Api\CreateApplicationTokenService;

/**
 * Every application token acts as System, so the audit log names the token too. Without it, two
 * integrations' changes look identical, and so does a leaked key's.
 */
function createWebhookWithToken(string $token): void
{
    test()->withToken($token)
        ->postJson('/api/application/webhooks', [
            'url' => 'https://billing.example.test/hooks',
            'events' => ['admin.webhook.*'],
        ])
        ->assertCreated();

    // The test client keeps the resolved user between requests otherwise.
    app('auth')->forgetGuards();
}

it('records which application token made a change', function () {
    $billing = app(CreateApplicationTokenService::class)->handle(admin(), 'Billing');

    createWebhookWithToken($billing->plainTextToken);

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_WEBHOOK_CREATED)->sole();
    expect($log->api_token_id)->toBe($billing->accessToken->getKey())
        ->and($log->api_token_name)->toBe('Billing');

    $this->actingAs(admin())
        ->getJson('/api/admin/audit-logs?filter[event]=admin.webhook.created')
        ->assertOk()
        ->assertJsonPath('items.0.actor.label', 'System')
        ->assertJsonPath('items.0.token.id', $billing->accessToken->getKey())
        ->assertJsonPath('items.0.token.name', 'Billing');
});

it('tells two integrations apart and filters by token', function () {
    $service = app(CreateApplicationTokenService::class);
    $billing = $service->handle(admin(), 'Billing');
    $monitoring = $service->handle(admin(), 'Monitoring');

    createWebhookWithToken($billing->plainTextToken);
    createWebhookWithToken($monitoring->plainTextToken);

    $this->actingAs(admin())
        ->getJson('/api/admin/audit-logs?filter[event]=admin.webhook.created&filter[api_token_id]='.$monitoring->accessToken->getKey())
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.token.name', 'Monitoring');
});

it('keeps the token name after the token is revoked', function () {
    $billing = app(CreateApplicationTokenService::class)->handle(admin(), 'Billing');

    createWebhookWithToken($billing->plainTextToken);

    PersonalAccessToken::query()->whereKey($billing->accessToken->getKey())->delete();

    $this->actingAs(admin())
        ->getJson('/api/admin/audit-logs?filter[event]=admin.webhook.created')
        ->assertOk()
        ->assertJsonPath('items.0.token.id', null)
        ->assertJsonPath('items.0.token.name', 'Billing');
});

it('puts the token in webhook payloads', function () {
    $billing = app(CreateApplicationTokenService::class)->handle(admin(), 'Billing');

    // The new webhook subscribes to admin.webhook.*, so it hears about its own creation.
    createWebhookWithToken($billing->plainTextToken);

    $delivery = WebhookDelivery::query()->where('event', 'admin.webhook.created')->sole();
    expect($delivery->payload['data']['token']['name'])->toBe('Billing');
});

it('shows a customer the token on their own changes only', function () {
    [$user, , , $server] = createServerModel();

    $own = Audit::record(AuditEvent::SERVER_RENAMED, subject: $server, actor: $user);
    $own->forceFill(['api_token_name' => 'Laptop script'])->save();

    $system = Audit::record(AuditEvent::SERVER_RENAMED, subject: $server, actor: SystemActor::instance());
    $system->forceFill(['api_token_name' => 'Billing'])->save();

    $items = collect($this->actingAs($user)
        ->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertOk()
        ->json('items'))
        ->keyBy(fn (array $item) => $item['actor']['type']);

    expect($items['user']['token']['name'])->toBe('Laptop script')
        ->and($items['system']['token'])->toBeNull();

    $names = collect($this->actingAs(admin())
        ->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertOk()
        ->json('items'))
        ->pluck('token.name')
        ->sort()
        ->values()
        ->all();

    expect($names)->toBe(['Billing', 'Laptop script']);
});

it('records a personal token the same way', function () {
    [$user, , , $server] = createServerModel();
    $key = app(CreateAccountTokenService::class)->handle($user, 'Laptop script');

    $this->actingAs($user->withAccessToken($key->accessToken));
    Audit::record(AuditEvent::SERVER_RENAMED, subject: $server);

    $key->accessToken->delete();

    $log = AuditLog::query()->forEvent(AuditEvent::SERVER_RENAMED)->sole();
    expect($log->api_token_id)->toBeNull()
        ->and($log->api_token_name)->toBe('Laptop script');
});

it('leaves the token empty for a browser session', function () {
    [$user, , , $server] = createServerModel();

    $this->actingAs($user);
    Audit::record(AuditEvent::SERVER_RENAMED, subject: $server);

    $log = AuditLog::query()->forEvent(AuditEvent::SERVER_RENAMED)->sole();
    expect($log->api_token_id)->toBeNull()
        ->and($log->api_token_name)->toBeNull();

    $this->getJson("/api/client/servers/{$server->uuid}/audit-logs")
        ->assertOk()
        ->assertJsonPath('items.0.token', null);
});
