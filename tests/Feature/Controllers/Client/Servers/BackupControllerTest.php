<?php

use App\Jobs\Server\MonitorBackupJob;
use App\Jobs\Server\MonitorBackupRestorationJob;
use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function testCreateBackup(
    bool $useSecondUser = false,
    bool $secondUserIsAdmin = false,
): Closure {
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
            "/api/client/servers/{$server->uuid}/backups",
            [
                'name' => 'Test Backup',
                'mode' => 'snapshot',
                'compression_type' => 'none',
                'is_locked' => false,
            ],
        );

        if ($useSecondUser && ! $secondUserIsAdmin) {
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
): Closure {
    return function () use ($useSecondUser, $secondUserIsAdmin) {
        Http::fake([
            '*/status/current' => Http::response(
                file_get_contents(
                    base_path('tests/Fixtures/Repositories/Server/GetStoppedServerStatusData.json'),
                ),
                200,
            ),
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

        $response = $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/backups/{$backup->uuid}/restore",
        );

        if ($useSecondUser && ! $secondUserIsAdmin) {
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
): Closure {
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

        if ($useSecondUser && ! $secondUserIsAdmin) {
            $response->assertNotFound();

            return;
        }

        $response->assertNoContent();
    };
}

it('can create backups', testCreateBackup());

it('can restore backups', testRestoreBackups());

it('can delete backups', testDeleteBackups());

describe('other servers', function () {
    beforeEach(function () {
        [$_, $_, $_, $server] = createServerModel();
        $this->backup = Backup::factory()->for($server)->create();
    });

    it("can't restore another's backup", function () {
        Http::fake([
            '*/status/current' => Http::response(
                file_get_contents(
                    base_path('tests/Fixtures/Repositories/Server/GetStoppedServerStatusData.json'),
                ),
                200,
            ),
            '*' => Http::response(['data' => 'dummy-upid'], 200),
        ]);

        [$user, $_, $_, $server] = createServerModel();

        $response = $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/backups/{$this->backup->uuid}/restore",
        );

        $response->assertNotFound();
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
