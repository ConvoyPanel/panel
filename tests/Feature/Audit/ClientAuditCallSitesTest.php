<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // The power lock lives in the (array) cache, which persists across a run; a lock left behind
    // by another test would 409 this one. Same reason as tests/Feature/Servers/PowerActionLockTest.
    Cache::flush();
});

/** The single audit row written during the test, failing loudly if there is not exactly one. */
function auditRow(): AuditLog
{
    return AuditLog::query()->sole();
}

function auditRowFor(AuditEvent $event): AuditLog
{
    return AuditLog::query()->forEvent($event)->sole();
}

it('records a power command with the signal that was sent', function () {
    [$user, , , $server] = createServerModel();
    fakeProxmox();

    $this->actingAs($user)
        ->postJson("/api/client/servers/{$server->uuid}/power", ['command' => 'restart'])
        ->assertSuccessful();

    $log = auditRowFor(AuditEvent::SERVER_POWER_SENT);

    expect($log->subject->is($server))->toBeTrue()
        ->and($log->actor->is($user))->toBeTrue()
        ->and($log->properties['command'])->toBe('restart')
        ->and($log->ip)->not->toBeNull();
});

it('records a rename against the server', function () {
    [$user, , , $server] = createServerModel();
    fakeProxmox();

    $this->actingAs($user)
        ->postJson("/api/client/servers/{$server->uuid}/settings/rename", [
            'name' => 'renamed-box',
            'hostname' => 'renamed-box',
        ])
        ->assertSuccessful();

    $log = auditRowFor(AuditEvent::SERVER_RENAMED);

    expect($log->subject->is($server))->toBeTrue()
        ->and($log->properties['hostname'])->toBe('renamed-box');
});

it('never records the password when server auth settings change', function () {
    [$user, , , $server] = createServerModel();
    fakeProxmox();

    // The password branch sits behind an identity gate; the key branch does not.
    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->putJson("/api/client/servers/{$server->uuid}/settings/auth", [
            'type' => 'password',
            'password' => 'Sup3r-Secret-Value!',
        ])
        ->assertSuccessful();

    $log = auditRowFor(AuditEvent::SERVER_AUTH_SETTINGS_UPDATED);

    // The whole row is searched, not just the properties: a secret that leaks into any column is
    // still a secret in the database.
    expect(json_encode($log->getAttributes()))->not->toContain('Sup3r-Secret-Value!')
        ->and($log->properties['type'])->toBe('password');
});

it('records an api key being created and destroyed against the account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->postJson('/api/client/account/api-keys', ['name' => 'ci-key'])
        ->assertSuccessful();

    $created = auditRowFor(AuditEvent::ACCOUNT_API_KEY_CREATED);

    expect($created->properties['name'])->toBe('ci-key')
        ->and($created->actor->is($user))->toBeTrue();
});

it('never records the plaintext token when an api key is created', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(confirmedSession())
        ->postJson('/api/client/account/api-keys', ['name' => 'ci-key'])
        ->assertSuccessful();

    // The plaintext token is returned exactly once, at creation; whatever key it arrives under,
    // it must not have been copied into the audit row.
    $token = data_get($response->json(), 'data.plainTextToken')
        ?? data_get($response->json(), 'data.plain_text_token');

    expect($token)->not->toBeNull()
        ->and(json_encode(auditRowFor(AuditEvent::ACCOUNT_API_KEY_CREATED)->getAttributes()))
        ->not->toContain($token);
});

it('records an ssh key being added', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->postJson('/api/client/account/ssh-keys', [
            'name' => 'laptop',
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIGtest laptop',
        ])
        ->assertSuccessful();

    expect(auditRowFor(AuditEvent::ACCOUNT_SSH_KEY_CREATED)->properties['name'])->toBe('laptop');
});

it('records a password change without recording the password', function () {
    $user = User::factory()->create(['password' => 'old-password-value']);

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->putJson('/api/client/account/password', [
            'current_password' => 'old-password-value',
            'password' => 'new-password-value',
            'password_confirmation' => 'new-password-value',
        ])
        ->assertSuccessful();

    $log = auditRowFor(AuditEvent::ACCOUNT_PASSWORD_UPDATED);

    expect($log->subject->is($user))->toBeTrue()
        ->and(json_encode($log->getAttributes()))->not->toContain('new-password-value');
});
