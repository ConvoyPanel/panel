<?php

use App\Enums\Api\ApiKeyType;
use App\Enums\Audit\AuditEvent;
use App\Enums\Server\ServerLifecycle;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Models\Server;
use App\Models\SystemActor;
use App\Models\User;
use App\Services\Api\CreateApplicationTokenService;
use Illuminate\Support\Facades\Queue;
use phpseclib3\Crypt\EC;

/**
 * The client area's per-server operations, reached with a panel-wide application token on
 * /api/application/servers/{server}/... instead of a customer's account.
 */
function serverOpsToken(array $abilities = ['*']): string
{
    $admin = User::factory()->create(['root_admin' => true]);

    return app(CreateApplicationTokenService::class)
        ->handle($admin, 'billing', $abilities)
        ->plainTextToken;
}

beforeEach(function () {
    fakeProxmox();
    Queue::fake();
});

it('takes a backup of a customer server with an application token', function () {
    [, , , $server] = createServerModel();

    $this->withToken(serverOpsToken())
        ->postJson("/api/application/servers/{$server->uuid}/backups", [
            'name' => 'Nightly',
            'mode' => 'snapshot',
            'compression_type' => 'none',
            'is_locked' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Nightly');

    $log = AuditLog::query()->where('event', AuditEvent::SERVER_BACKUP_CREATED->value)->sole();
    expect($log->actor)->toBeInstanceOf(SystemActor::class);
});

it('resets a root password without an identity confirmation', function () {
    [, , , $server] = createServerModel();

    $this->withToken(serverOpsToken())
        ->putJson("/api/application/servers/{$server->uuid}/settings/auth", [
            'type' => 'password',
            'password' => 'Correct-Horse-9!',
        ])
        ->assertNoContent();
});

it('sets ssh keys and counts them', function () {
    [, , , $server] = createServerModel();
    $keys = collect([1, 2])
        ->map(fn () => EC::createKey('Ed25519')->getPublicKey()->toString('OpenSSH'))
        ->implode("\n");

    $this->withToken(serverOpsToken())
        ->putJson("/api/application/servers/{$server->uuid}/settings/auth", [
            'type' => 'ssh_keys',
            'ssh_keys' => $keys,
        ])
        ->assertNoContent();

    $log = AuditLog::query()->where('event', AuditEvent::SERVER_AUTH_SETTINGS_UPDATED->value)->sole();
    expect($log->properties['ssh_key_count'])->toBe(2);
});

it('rebuilds a server without an identity confirmation', function () {
    [, , , $server] = createServerModel();
    $image = installableImage();

    $this->withToken(serverOpsToken())
        ->postJson("/api/application/servers/{$server->uuid}/settings/reinstall", [
            'image_uuid' => $image->uuid,
            'account_password' => 'Correct-Horse-9',
            'start_on_completion' => true,
        ])
        ->assertNoContent();

    expect($server->deployments()->count())->toBe(1);
});

it('reports install progress', function () {
    [, , , $server] = createServerModel();

    $this->withToken(serverOpsToken(['servers:read']))
        ->getJson("/api/application/servers/{$server->uuid}/deployment")
        ->assertNoContent();
});

it('opens a console session for the panel rather than a person', function () {
    [, , , $server] = createServerModel();

    // The factory node has no agent, so this stops at the agent check. Getting that far means
    // the session service took the system actor, which it used to refuse by type.
    $this->withToken(serverOpsToken())
        ->postJson("/api/application/servers/{$server->uuid}/create-console-session", ['type' => 'novnc'])
        ->assertConflict()
        ->assertJsonPath('message', "This server's node does not have an Anchor agent installed.");
});

it('holds a read-only token to reads', function () {
    [, , , $server] = createServerModel();
    $token = serverOpsToken(['servers:read']);

    $this->withToken($token)
        ->getJson("/api/application/servers/{$server->uuid}/backups")
        ->assertOk();

    $this->withToken($token)
        ->postJson("/api/application/servers/{$server->uuid}/backups", [
            'name' => 'Nightly',
            'mode' => 'snapshot',
            'compression_type' => 'none',
        ])
        ->assertForbidden();
});

it('refuses a token scoped to another resource', function () {
    [, , , $server] = createServerModel();

    $this->withToken(serverOpsToken(['nodes:*']))
        ->getJson("/api/application/servers/{$server->uuid}/backups")
        ->assertForbidden();
});

it('does not reach a backup through a server that does not own it', function () {
    [, , , $server] = createServerModel();
    [, , , $other] = createServerModel();
    $backup = Backup::factory()->for($other)->create();

    $this->withToken(serverOpsToken())
        ->deleteJson("/api/application/servers/{$server->uuid}/backups/{$backup->uuid}")
        ->assertNotFound();
});

it('refuses operations on a server that is still installing', function () {
    [, , , $server] = createServerModel();
    $server->update(['lifecycle' => ServerLifecycle::INSTALLING]);

    $this->withToken(serverOpsToken())
        ->getJson("/api/application/servers/{$server->uuid}/backups")
        ->assertStatus(409);
});

it('still operates on a suspended server', function () {
    [, , , $server] = createServerModel();
    $server->forceFill(['suspended_at' => now()])->save();

    $this->withToken(serverOpsToken())
        ->getJson("/api/application/servers/{$server->uuid}/backups")
        ->assertOk();
});

it('lets a personal token set a root password on its own server', function () {
    [$user, , , $server] = createServerModel();
    $token = $user->createToken('cli', ApiKeyType::ACCOUNT, ['servers:*'])->plainTextToken;

    $this->withToken($token)
        ->putJson("/api/client/servers/{$server->uuid}/settings/auth", [
            'type' => 'password',
            'password' => 'Correct-Horse-9!',
        ])
        ->assertNoContent();
});

it('still asks a browser session to confirm identity', function () {
    [$user, , , $server] = createServerModel();

    $this->actingAs($user)
        ->putJson("/api/client/servers/{$server->uuid}/settings/auth", [
            'type' => 'password',
            'password' => 'Correct-Horse-9!',
        ])
        ->assertForbidden();
});
