<?php

use App\Enums\Server\ServerPermission;
use App\Models\Backup;
use App\Models\User;

/**
 * Per-permission authorization on the client server API.
 *
 * Every case is run twice: once with the permission granted and once with a different one held,
 * because a gate that always allows and a gate that always denies both pass a one-sided test.
 * The "denied" account is given an unrelated permission rather than none, so the assertion is
 * that *this* permission is what opens the endpoint rather than merely being a sub-user.
 *
 * The allow side asserts "not refused" rather than a 2xx, through the shared `assertReached()`
 * helper; see its docblock in tests/Pest.php.
 */
beforeEach(function () {
    fakeProxmox();

    [$this->owner, , , $this->server] = createServerModel();
});

/** @return array<string, array{ServerPermission, string, string, array<string, mixed>}> */
dataset('gated endpoints', [
    'read the activity feed' => [ServerPermission::ACTIVITY_READ, 'getJson', '/audit-logs', []],
    'read statistics' => [ServerPermission::STATISTICS_READ, 'getJson', '/statistics?from=hour', []],
    'start' => [ServerPermission::POWER_START, 'postJson', '/power', ['command' => 'start']],
    'shut down' => [ServerPermission::POWER_STOP, 'postJson', '/power', ['command' => 'shutdown']],
    'restart' => [ServerPermission::POWER_RESTART, 'postJson', '/power', ['command' => 'restart']],
    'kill' => [ServerPermission::POWER_KILL, 'postJson', '/power', ['command' => 'kill']],
    'open a console session' => [ServerPermission::CONSOLE_SESSION, 'postJson', '/create-console-session', ['type' => 'novnc']],
    'read the serial console' => [ServerPermission::CONSOLE_CONFIGURE, 'getJson', '/settings/hardware/serial-console', []],
    'list backups' => [ServerPermission::BACKUP_READ, 'getJson', '/backups', []],
    'read firewall rules' => [ServerPermission::FIREWALL_READ, 'getJson', '/firewall/rules', []],
    'rename' => [ServerPermission::SETTINGS_RENAME, 'postJson', '/settings/rename', ['name' => 'Renamed', 'hostname' => 'renamed.test']],
    'read devices' => [ServerPermission::SETTINGS_BOOT_ORDER, 'getJson', '/settings/hardware/storage', []],
    'list ISOs' => [ServerPermission::SETTINGS_MEDIA, 'getJson', '/settings/hardware/isos', []],
    'read nameservers' => [ServerPermission::SETTINGS_NETWORK, 'getJson', '/settings/network', []],
    'read OS credentials' => [ServerPermission::SETTINGS_AUTH, 'getJson', '/settings/auth', []],
    'list installable images' => [ServerPermission::SETTINGS_REINSTALL, 'getJson', '/settings/image-groups', []],
]);

it('allows a sub-user holding the permission', function (
    ServerPermission $permission,
    string $method,
    string $path,
    array $body,
) {
    $user = subuser($this->server, [$permission]);

    assertReached($this->actingAs($user)->{$method}(
        "/api/client/servers/{$this->server->uuid}{$path}",
        $body,
    ));
})->with('gated endpoints');

it('denies a sub-user holding a different permission', function (
    ServerPermission $permission,
    string $method,
    string $path,
    array $body,
) {
    // A permission they do have, so the refusal is about this one rather than about being a
    // stranger. ACTIVITY_READ opens nothing else, which makes it the safe stand-in -- except on
    // the activity feed itself, where STATISTICS_READ takes its place.
    $unrelated = $permission === ServerPermission::ACTIVITY_READ
        ? ServerPermission::STATISTICS_READ
        : ServerPermission::ACTIVITY_READ;

    $user = subuser($this->server, [$unrelated]);

    $this->actingAs($user)
        ->{$method}("/api/client/servers/{$this->server->uuid}{$path}", $body)
        ->assertForbidden();
})->with('gated endpoints');

it('lets the owner do everything without a stored grant', function (
    ServerPermission $permission,
    string $method,
    string $path,
    array $body,
) {
    assertReached($this->actingAs($this->owner)->{$method}(
        "/api/client/servers/{$this->server->uuid}{$path}",
        $body,
    ));
})->with('gated endpoints');

it('hides the server from somebody it was never shared with', function () {
    $stranger = User::factory()->create();

    // 404 rather than 403: whether a server exists is itself something only the people who can
    // reach it should learn.
    $this->actingAs($stranger)
        ->getJson("/api/client/servers/{$this->server->uuid}")
        ->assertNotFound();
});

it('splits the power endpoint four ways', function () {
    $user = subuser($this->server, [ServerPermission::POWER_START]);

    $send = fn (string $command) => $this->actingAs($user)->postJson(
        "/api/client/servers/{$this->server->uuid}/power",
        ['command' => $command],
    );

    // `resume` rides with `start` and `suspend` with `shutdown`: what matters to the guest is
    // whether it is asked to stop or simply stopped.
    assertReached($send('start'));
    assertReached($send('resume'));
    $send('shutdown')->assertForbidden();
    $send('restart')->assertForbidden();
    $send('kill')->assertForbidden();
    $send('reset')->assertForbidden();
});

it('gates each backup action separately', function () {
    $backup = Backup::factory()->create(['server_id' => $this->server->id]);
    $reader = subuser($this->server, [ServerPermission::BACKUP_READ]);

    assertReached($this->actingAs($reader)->getJson(
        "/api/client/servers/{$this->server->uuid}/backups",
    ));

    $this->actingAs($reader)
        ->postJson("/api/client/servers/{$this->server->uuid}/backups", [
            'name' => 'Nope',
            'mode' => 'snapshot',
            'compression_type' => 'none',
            'is_locked' => false,
        ])
        ->assertForbidden();

    $this->actingAs($reader)
        ->postJson("/api/client/servers/{$this->server->uuid}/backups/{$backup->uuid}/restore")
        ->assertForbidden();

    $this->actingAs($reader)
        ->deleteJson("/api/client/servers/{$this->server->uuid}/backups/{$backup->uuid}")
        ->assertForbidden();
});

it('lets a sub-user with no permissions see the server and nothing else', function () {
    // An empty grant is a real state, not a mistake: it is what "I have shared this with you and
    // not decided what you may do yet" looks like.
    $user = subuser($this->server, []);

    $this->actingAs($user)
        ->getJson("/api/client/servers/{$this->server->uuid}")
        ->assertOk()
        ->assertJsonPath('data.isOwner', false)
        ->assertJsonPath('data.permissions', []);

    $this->actingAs($user)
        ->getJson("/api/client/servers/{$this->server->uuid}/audit-logs")
        ->assertForbidden();
});

it('reports the caller\'s own permissions on the server payload', function () {
    $user = subuser($this->server, [ServerPermission::POWER_START, ServerPermission::BACKUP_READ]);

    $this->actingAs($user)
        ->getJson("/api/client/servers/{$this->server->uuid}")
        ->assertOk()
        ->assertJsonPath('data.permissions', ['power.start', 'backup.read']);

    // The owner gets the whole catalog rather than a stored subset, so the frontend has one shape
    // to read instead of an ownership special case.
    $this->actingAs($this->owner)
        ->getJson("/api/client/servers/{$this->server->uuid}")
        ->assertOk()
        ->assertJsonPath('data.isOwner', true)
        ->assertJsonCount(count(ServerPermission::cases()), 'data.permissions');
});

it('lists a shared server alongside the ones they own', function () {
    $user = subuser($this->server, []);

    $this->actingAs($user)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.uuid', $this->server->uuid);

    // And still not anyone else's.
    [, , , $other] = createServerModel();

    $this->actingAs($user)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(1, 'items');

    expect($other->uuid)->not->toBe($this->server->uuid);
});

it('stops a revoked sub-user at the door', function () {
    $user = subuser($this->server, [ServerPermission::POWER_START]);

    $this->server->subusers()->where('user_id', '=', $user->id)->delete();

    $this->actingAs($user)
        ->postJson("/api/client/servers/{$this->server->uuid}/power", ['command' => 'start'])
        ->assertNotFound();
});
