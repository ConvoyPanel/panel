<?php

use App\Enums\Audit\AuditEvent;
use App\Enums\Audit\AuditVisibility;
use App\Models\AuditLog;
use App\Models\Location;
use App\Models\User;

it('records a suspension against the server so the owner can see it', function () {
    [, , , $server] = createServerModel();
    fakeProxmox();

    $this->actingAs(admin())
        ->postJson("/api/admin/servers/{$server->id}/settings/suspend")
        ->assertSuccessful();

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_SERVER_SUSPENDED)->sole();

    // Subject is the server, so this reaches the owner's activity feed rather than being
    // admin-only. That is deliberate: it is something done to their server.
    expect($log->subject->is($server))->toBeTrue()
        ->and($log->event->visibility())->toBe(AuditVisibility::CLIENT);
});

it('survives the deletion of the admin who performed the action', function () {
    $staff = admin();
    $staff->forceFill(['name' => 'Departing Admin'])->save();

    $location = Location::factory()->create();

    $this->actingAs($staff)
        ->deleteJson("/api/admin/locations/{$location->id}")
        ->assertSuccessful();

    $staff->delete();

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_LOCATION_DELETED)->sole();

    expect($log->actor)->toBeNull()
        ->and($log->actor_label)->toBe('Departing Admin')
        ->and($log->properties['short_code'])->toBe($location->short_code);
});

it('never records the plaintext value of an application token', function () {
    $response = $this->actingAs(admin())
        ->withSession(confirmedSession())
        ->postJson('/api/admin/tokens', ['name' => 'deploy-bot'])
        ->assertSuccessful();

    $token = data_get($response->json(), 'data.plainTextToken')
        ?? data_get($response->json(), 'data.plain_text_token');

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_TOKEN_CREATED)->sole();

    expect($token)->not->toBeNull()
        ->and(json_encode($log->getAttributes()))->not->toContain($token)
        ->and($log->properties['name'])->toBe('deploy-bot');
});

it('records which fields a user update touched without recording their values', function () {
    $target = User::factory()->create(['email' => 'before@example.com']);

    $this->actingAs(admin())
        ->patchJson("/api/admin/users/{$target->id}", [
            'name' => $target->name,
            'email' => 'after@example.com',
            'password' => 'Brand-New-Passw0rd!',
            'root_admin' => false,
        ])
        ->assertSuccessful();

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_USER_UPDATED)->sole();

    expect($log->properties['email'])->toBe('after@example.com')
        ->and($log->properties['password_changed'])->toBeTrue()
        ->and(json_encode($log->getAttributes()))->not->toContain('Brand-New-Passw0rd!');
});

it('keeps a deleted user record forever', function () {
    $target = User::factory()->create(['name' => 'Removed Person']);

    $this->actingAs(admin())
        ->deleteJson("/api/admin/users/{$target->id}")
        ->assertSuccessful();

    $this->artisan('maintenance:prune-audit-logs', ['--prune-days' => 1])->assertSuccessful();

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_USER_DELETED)->sole();

    expect($log->properties['name'])->toBe('Removed Person');
});
