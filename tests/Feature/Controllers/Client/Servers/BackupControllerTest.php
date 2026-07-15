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

        $response->assertCreated()
                 ->assertJsonPath('data.name', 'Test Backup')
                 ->assertJsonPath('data.isLocked', false);

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

describe('index quota totals', function () {
    it('reports count and size across every non-failed backup, not just the page', function () {
        [$user, $_, $_, $server] = createServerModel();

        // StorageSizeCast persists MiB, so these are 4 MiB each on disk and
        // must come back as bytes -- a raw SUM() would report 8, not 8 MiB.
        Backup::factory()->count(2)->create([
            'server_id' => $server->id,
            'size' => 4 * 1024 * 1024,
            'completed_at' => now(),
            'error_code' => null,
        ]);

        // Failed backups are excluded from both figures.
        Backup::factory()->create([
            'server_id' => $server->id,
            'size' => 512 * 1024 * 1024,
            'completed_at' => now(),
            'error_code' => 'other',
        ]);

        $response = $this->actingAs($user)->getJson(
            "/api/client/servers/{$server->uuid}/backups?per_page=1",
        );

        $response->assertOk()
                 ->assertJsonCount(1, 'items')
                 ->assertJsonPath('backupCount', 2)
                 ->assertJsonPath('backupSize', 8 * 1024 * 1024);
    });
});
