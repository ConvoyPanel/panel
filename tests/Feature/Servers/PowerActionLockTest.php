<?php

use App\Models\User;
use App\Services\Servers\Power\ServerPowerLockService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // The lock lives in the (array) cache, which persists across tests in a run;
    // start each case from a clean slate so ordering can't leak a held lock.
    Cache::flush();
});

/**
 * Fakes Proxmox with a task-status endpoint that walks the given statuses, one
 * per read, holding on the last — so a test can play out an action completing
 * the way the dashboard's polling would see it. 'running' means the task is
 * still working; 'stopped' means it has finished.
 *
 * The guest `status/current` read (which the state endpoint still makes to build
 * its response) is held at a constant 'running' on purpose: the lock no longer
 * looks at guest state at all, and pinning it lets the fast-reboot case assert
 * exactly that.
 */
function fakeProxmoxTask(string ...$statuses): void
{
    $reads = 0;

    fakeProxmox([
        '*/tasks/*/status' => function () use ($statuses, &$reads) {
            $status = $statuses[min($reads++, count($statuses) - 1)];

            return Http::response(['data' => [
                'upid' => 'UPID:pve:00001:00000001:00000001:qmreboot:100:root@pam:',
                'node' => 'pve',
                'pid' => 1,
                'pstart' => 1,
                'starttime' => 1700000000,
                'type' => 'qmreboot',
                'id' => '100',
                'user' => 'root@pam',
                'status' => $status,
                'exitstatus' => $status === 'stopped' ? 'OK' : null,
            ]], 200);
        },
        '*/status/current' => Http::response(['data' => [
            'status' => 'running',
            'uptime' => 60,
            'cpu' => 0,
            'maxmem' => 2147483648,
            'mem' => 0,
        ]], 200),
    ]);
}

/**
 * Like fakeProxmoxTask, but the task finishes with the given non-OK exit string
 * — a Proxmox error such as a shutdown the guest refused — so a test can play
 * out an action that fails rather than one that succeeds.
 */
function fakeProxmoxTaskFailing(string $exit, string ...$statuses): void
{
    $reads = 0;

    fakeProxmox([
        '*/tasks/*/status' => function () use ($exit, $statuses, &$reads) {
            $status = $statuses[min($reads++, count($statuses) - 1)];

            return Http::response(['data' => [
                'upid' => 'UPID:pve:00001:00000001:00000001:qmshutdown:100:root@pam:',
                'node' => 'pve',
                'pid' => 1,
                'pstart' => 1,
                'starttime' => 1700000000,
                'type' => 'qmshutdown',
                'id' => '100',
                'user' => 'root@pam',
                'status' => $status,
                'exitstatus' => $status === 'stopped' ? $exit : null,
            ]], 200);
        },
        '*/status/current' => Http::response(['data' => [
            'status' => 'running',
            'uptime' => 60,
            'cpu' => 0,
            'maxmem' => 2147483648,
            'mem' => 0,
        ]], 200),
    ]);
}

it('rejects a second power command while one is already in flight', function () {
    fakeProxmox();

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    // First command claims the lock and reaches Proxmox.
    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'start'])
        ->assertNoContent();

    // Second, while the lock is held, is rejected with the curated 409 code and
    // never touches Proxmox.
    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'shutdown'])
        ->assertConflict()
        ->assertJsonPath('code', 'power_action_in_progress');

    // Exactly one power request went out (the first). The shutdown was blocked.
    Http::assertSentCount(1);
});

it('surfaces the pending power action on the server state', function () {
    // Task still running, so the lock is held across the poll.
    fakeProxmoxTask('running');

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    // No action in flight yet.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.pendingPowerAction', null);

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'start'])
        ->assertNoContent();

    // Now the state echoes which command is holding the lock.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.pendingPowerAction.command', 'start');
});

it('clears the pending action once the Proxmox task finishes', function () {
    // The task is still running on the first poll, finished on the next.
    fakeProxmoxTask('running', 'stopped');

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'kill'])
        ->assertNoContent();

    // Task still running, so the action is still pending and the UI keeps the
    // controls locked out.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.pendingPowerAction.command', 'kill');

    // Task finished: the lock is gone and the user can act again without waiting
    // out the TTL.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.pendingPowerAction', null);

    expect(app(ServerPowerLockService::class)->pending($server))->toBeNull();

    // And the next command is accepted rather than 409'd.
    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'start'])
        ->assertNoContent();
});

it('surfaces a successful outcome once the task finishes, tied to the action that ran', function () {
    fakeProxmoxTask('running', 'stopped');

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'start'])
        ->assertNoContent();

    // While running there is no result yet — just the pending action, whose
    // requestedAt the outcome must later carry so the UI can correlate them.
    $requestedAt = $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertJsonPath('data.lastPowerAction', null)
        ->json('data.pendingPowerAction.requestedAt');

    // Task done: the outcome appears alongside the now-cleared pending action.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.pendingPowerAction', null)
        ->assertJsonPath('data.lastPowerAction.command', 'start')
        ->assertJsonPath('data.lastPowerAction.ok', true)
        ->assertJsonPath('data.lastPowerAction.exitStatus', 'OK')
        ->assertJsonPath('data.lastPowerAction.requestedAt', $requestedAt);
});

it('surfaces a failed outcome with the Proxmox exit string', function () {
    fakeProxmoxTaskFailing("command 'qm shutdown 100' failed: got timeout", 'running', 'stopped');

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'shutdown'])
        ->assertNoContent();

    // Running poll — still pending, no outcome.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertJsonPath('data.pendingPowerAction.command', 'shutdown');

    // Finished-but-failed: the lock clears and the failure is reported with the
    // raw exit string for the UI to show as detail.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.pendingPowerAction', null)
        ->assertJsonPath('data.lastPowerAction.command', 'shutdown')
        ->assertJsonPath('data.lastPowerAction.ok', false)
        ->assertJsonPath('data.lastPowerAction.exitStatus', "command 'qm shutdown 100' failed: got timeout");
});

it('clears a reboot as soon as its task finishes, even if the guest never appears to leave running', function () {
    // The whole point of tracking the task instead of guest state: a reboot
    // begins and ends on 'running', and here the guest is pinned to 'running'
    // for every poll — the cycle is never visible in guest state. State-based
    // resolution could only clear this on the TTL; task-based resolution clears
    // it the instant Proxmox reports the reboot task done.
    fakeProxmoxTask('running', 'stopped');

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'restart'])
        ->assertNoContent();

    // Task running — still pending.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertJsonPath('data.pendingPowerAction.command', 'restart');

    // Task done — cleared, despite the guest reading 'running' throughout.
    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertJsonPath('data.pendingPowerAction', null);
});

it('keeps the lock in place when Proxmox cannot report on the task', function () {
    // The task-status probe fails (Proxmox unreachable, or the task has rotated
    // out of its log). The state read must not fail with it — the lock simply
    // stays until the TTL clears it.
    fakeProxmox([
        '*/tasks/*/status' => Http::response('gateway timeout', 504),
        // getState still needs the guest read to succeed; only the task probe
        // is broken here.
        '*/status/current' => Http::response(['data' => [
            'status' => 'running',
            'uptime' => 60,
            'cpu' => 0,
            'maxmem' => 2147483648,
            'mem' => 0,
        ]], 200),
    ]);

    [$_owner, $_, $_, $server] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$server->uuid}/state", ['state' => 'shutdown'])
        ->assertNoContent();

    $this->actingAs($admin)
        ->getJson("/api/admin/servers/{$server->uuid}/state")
        ->assertOk()
        ->assertJsonPath('data.pendingPowerAction.command', 'shutdown');
});

it('lets a different server be powered while another holds its lock', function () {
    fakeProxmox();

    [$_owner, $_, $_, $serverA] = createServerModel();
    [$_owner2, $_, $_, $serverB] = createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);

    // Lock is per-server, so B is unaffected by A's in-flight action.
    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$serverA->uuid}/state", ['state' => 'start'])
        ->assertNoContent();

    $this->actingAs($admin)
        ->patchJson("/api/admin/servers/{$serverB->uuid}/state", ['state' => 'start'])
        ->assertNoContent();

    Http::assertSentCount(2);

    expect(app(ServerPowerLockService::class)->pending($serverA))->not->toBeNull();
    expect(app(ServerPowerLockService::class)->pending($serverB))->not->toBeNull();
});
