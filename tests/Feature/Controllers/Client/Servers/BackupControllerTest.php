<?php

use Convoy\Jobs\Server\MonitorBackupJob;
use Convoy\Jobs\Server\MonitorBackupRestorationJob;
use Convoy\Models\Backup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Http::preventStrayRequests());

it('can create backups', function () {
    Queue::fake();
    Http::fake([
        '*' => Http::response(['data' => 'upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/backups", [
        'name' => 'Test Backup',
        'mode' => 'snapshot',
        'compression_type' => 'none',
        'is_locked' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Test Backup')
        ->assertJsonPath('data.is_locked', (int) false);

    Queue::assertPushed(MonitorBackupJob::class);
});

it('can restore backups', function () {
    Queue::fake();
    Http::fake([
        '*/status/current' => Http::response(file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetStoppedServerStatusData.json')), 200),
        '*' => Http::response(['data' => 'dummy-upid'], 200),

    ]);

    [$user, $_, $_, $server] = createServerModel();

    $backup = Backup::factory()->create([
        'is_successful' => true,
        'is_locked' => false,
        'server_id' => $server->id,
    ]);

    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/backups/{$backup->uuid}/restore");

    $response->assertNoContent();

    Queue::assertPushed(MonitorBackupRestorationJob::class);
});

it('can delete backups', function () {
    Http::fake([
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $backup = Backup::factory()->create([
        'is_successful' => true,
        'is_locked' => false,
        'server_id' => $server->id,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/client/servers/{$server->uuid}/backups/{$backup->uuid}");

    $response->assertNoContent();
});
