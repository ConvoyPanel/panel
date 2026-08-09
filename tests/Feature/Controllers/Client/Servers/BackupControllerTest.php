<?php

use Convoy\Jobs\Server\MonitorBackupJob;
use Convoy\Jobs\Server\MonitorBackupRestorationJob;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function fakeStoppedServer(): void
{
    Http::fake([
        '*/status/current' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Server/GetStoppedServerStatusData.json'),
            ), 200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);
}

function testCreateBackup(
    bool $useSecondUser = false,
    bool $secondUserIsAdmin = false,
): Closure
{
    return function () use ($useSecondUser, $secondUserIsAdmin) {
        Http::fake([
            '*' => Http::response(['data' => 'upid'], 200),
        ]);

        [$user, $_, $_, $server] = createServerModel();

        if ($useSecondUser) {
            $user = User::factory()->create([
                'root_admin' => $secondUserIsAdmin,
            ]);
        }

        $response = $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/backups", [
            'name' => 'Test Backup',
            'mode' => 'snapshot',
            'compression_type' => 'none',
            'is_locked' => false,
        ],
        );

        if ($useSecondUser && !$secondUserIsAdmin) {
            $response->assertNotFound();

            return;
        }

        $response->assertOk()
                 ->assertJsonPath('data.name', 'Test Backup')
                 ->assertJsonPath('data.is_locked', 0);

        Queue::assertPushed(MonitorBackupJob::class);
    };
}

function testRestoreBackups(
    bool $useSecondUser = false,
    bool $secondUserIsAdmin = false,
): Closure
{
    return function () use ($useSecondUser, $secondUserIsAdmin) {
        fakeStoppedServer();

        [$user, $_, $_, $server] = createServerModel();

        if ($useSecondUser) {
            $user = User::factory()->create([
                'root_admin' => $secondUserIsAdmin,
            ]);
        }

        $backup = Backup::factory()->create([
            'is_successful' => true,
            'is_locked' => false,
            'server_id' => $server->id,
        ]);

        $response = $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/backups/{$backup->uuid}/restore",
        );

        if ($useSecondUser && !$secondUserIsAdmin) {
            $response->assertNotFound();

            return;
        }

        $response->assertNoContent();

        Queue::assertPushed(MonitorBackupRestorationJob::class);
    };
}

function testDeleteBackups(
    bool $useSecondUser = false,
    bool $secondUserIsAdmin = false,
): Closure
{
    return function () use ($useSecondUser, $secondUserIsAdmin) {
        Http::fake([
            '*' => Http::response(['data' => 'dummy-upid'], 200),
        ]);

        [$user, $_, $_, $server] = createServerModel();

        if ($useSecondUser) {
            $user = User::factory()->create([
                'root_admin' => $secondUserIsAdmin,
            ]);
        }


        $backup = Backup::factory()->create([
            'is_successful' => true,
            'is_locked' => false,
            'server_id' => $server->id,
        ]);


        $response = $this->actingAs($user)->deleteJson(
            "/api/client/servers/{$server->uuid}/backups/{$backup->uuid}",
        );

        if ($useSecondUser && !$secondUserIsAdmin) {
            $response->assertNotFound();

            return;
        }

        $response->assertNoContent();
    };
}

it('can create backups', testCreateBackup());

it('can restore backups', testRestoreBackups());

it('can delete backups', testDeleteBackups());

/*
 * Nothing in the restore/delete path compares the backup's owner to the server
 * in the URL: neither form request inspects {backup}, BackupController passes
 * both straight through, and BackupDeletionService::handle() isn't even given
 * the server. The scoped route-model binding on the /api/client group
 * (RouteServiceProvider) is the only thing that resolves {backup} through
 * {server}, so these tests assert the 404 *and* that nothing reached Proxmox —
 * a status code alone wouldn't catch a regression that 404s after acting.
 */
describe('other servers', function () {
    beforeEach(function () {
        [$_, $_, $_, $server] = createServerModel();

        // Restorable and unlocked, so a passing test can only mean the request
        // was rejected on ownership rather than on the backup's own state.
        $this->backup = Backup::factory()->for($server)->create([
            'is_successful' => true,
            'is_locked' => false,
        ]);
    });

    it("can't restore another's backup", function () {
        fakeStoppedServer();

        [$user, $_, $_, $server] = createServerModel();

        $response = $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/backups/{$this->backup->uuid}/restore",
        );

        $response->assertNotFound();

        Http::assertNothingSent();
        Queue::assertNotPushed(MonitorBackupRestorationJob::class);
        expect($server->refresh()->status)->toBeNull();
    });

    it("can't delete another's backup", function () {
        Http::fake([
            '*' => Http::response(['data' => 'upid'], 200),
        ]);

        [$user, $_, $_, $server] = createServerModel();

        $response = $this->actingAs($user)->deleteJson(
            "/api/client/servers/{$server->uuid}/backups/{$this->backup->uuid}",
        );

        $response->assertNotFound();

        Http::assertNothingSent();
        $this->assertNotSoftDeleted($this->backup);
    });
});

/*
 * The caller genuinely owns {server} here, so AuthenticateServerAccess and the
 * ServerPolicy both pass — only the scoped binding rejects the mismatched
 * {backup}. That isolates the control the cases above depend on.
 */
describe('another server owned by the same user', function () {
    beforeEach(function () {
        [$user, $_, $node, $server] = createServerModel();

        $this->user = $user;
        $this->server = $server;
        $this->otherServer = Server::factory()->create([
            'user_id' => $user->id,
            'node_id' => $node->id,
            // ServerFactory picks a random vmid and both servers share a node,
            // so pin it rather than leave a collision to chance.
            'vmid' => $server->vmid + 1,
        ]);
        $this->backup = Backup::factory()->for($this->otherServer)->create([
            'is_successful' => true,
            'is_locked' => false,
        ]);
    });

    it("can't restore a backup belonging to the user's other server", function () {
        fakeStoppedServer();

        $response = $this->actingAs($this->user)->postJson(
            "/api/client/servers/{$this->server->uuid}/backups/{$this->backup->uuid}/restore",
        );

        $response->assertNotFound();

        Http::assertNothingSent();
        Queue::assertNotPushed(MonitorBackupRestorationJob::class);
        expect($this->server->refresh()->status)->toBeNull();
    });

    it("can't delete a backup belonging to the user's other server", function () {
        Http::fake([
            '*' => Http::response(['data' => 'upid'], 200),
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            "/api/client/servers/{$this->server->uuid}/backups/{$this->backup->uuid}",
        );

        $response->assertNotFound();

        Http::assertNothingSent();
        $this->assertNotSoftDeleted($this->backup);
    });
});

describe('admin', function () {
    it('can create backups', testCreateBackup(true, true));

    it('can restore backups', testRestoreBackups(true, true));

    it('can delete backups', testDeleteBackups(true, true));
});

describe('unauthorized users', function () {
    it("can't create backups", testCreateBackup(true));

    it("can't restore backups", testRestoreBackups(true));

    it("can't delete backups", testDeleteBackups(true));
});