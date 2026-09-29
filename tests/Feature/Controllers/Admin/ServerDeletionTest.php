<?php

use Convoy\Enums\Server\Status;
use Convoy\Jobs\Server\DeleteServerJob;
use Convoy\Jobs\Server\MonitorStateJob;
use Convoy\Jobs\Server\PurgeBackupsJob;
use Convoy\Jobs\Server\SendPowerCommandJob;
use Convoy\Jobs\Server\WaitUntilVmIsDeletedJob;
use Convoy\Models\Backup;
use Convoy\Models\Server;
use Convoy\Models\User;
use Convoy\Services\Backups\PurgeBackupsService;
use Convoy\Services\Servers\ServerBuildService;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function fakeVmPresent(): void
{
    Http::fake([
        '*/config' => Http::response(
            file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json')),
            200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);
}

function fakeVmGone(): void
{
    Http::fake([
        '*/config' => Http::response([
            'data' => null,
            'message' => "Configuration file 'nodes/proxmox/qemu-server/100.conf' does not exist\n",
        ], 500),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);
}

function deleteAsAdmin(Server $server, array $data = [])
{
    return test()->actingAs(User::factory()->create(['root_admin' => true]))
                 ->deleteJson("/api/admin/servers/{$server->uuid}", $data);
}

it('deletes the backups, then the VM, then the Convoy entry', function () {
    fakeVmPresent();
    [$_, $_, $_, $server] = createServerModel();

    deleteAsAdmin($server)->assertNoContent();

    expect($server->fresh()->status)->toBe(Status::DELETING->value);
    Queue::assertPushedWithChain(PurgeBackupsJob::class, [
        SendPowerCommandJob::class,
        MonitorStateJob::class,
        DeleteServerJob::class,
        WaitUntilVmIsDeletedJob::class,
        CallQueuedClosure::class,
    ]);
});

it('only removes the Convoy entry when the VM is already gone from the node', function () {
    fakeVmGone();
    [$_, $_, $_, $server] = createServerModel();

    deleteAsAdmin($server)->assertNoContent();

    Queue::assertPushedWithChain(PurgeBackupsJob::class, [CallQueuedClosure::class]);
});

it('leaves the server alone when the node cannot be asked', function () {
    Http::fake(['*' => Http::response(['data' => null, 'message' => 'proxy error'], 503)]);
    [$_, $_, $_, $server] = createServerModel();

    deleteAsAdmin($server)->assertServerError();

    // An outage must not read as "VM already gone" and drop the server.
    expect($server->fresh())->not->toBeNull()
        ->and($server->fresh()->status)->toBeNull();
    Queue::assertNothingPushed();
});

it('can retry a delete that failed', function () {
    fakeVmPresent();
    [$_, $_, $_, $server] = createServerModel();
    $server->update(['status' => Status::DELETION_FAILED->value]);

    deleteAsAdmin($server)->assertNoContent();

    expect($server->fresh()->status)->toBe(Status::DELETING->value);
});

it("won't delete while another operation is running on the server", function (Status $status) {
    fakeVmPresent();
    [$_, $_, $_, $server] = createServerModel();
    $server->update(['status' => $status->value]);

    deleteAsAdmin($server)->assertConflict();

    Queue::assertNothingPushed();
})->with([Status::INSTALLING, Status::DELETING, Status::RESTORING_BACKUP]);

it('disconnects without touching the node, from any state', function (?Status $status) {
    Http::fake();
    [$_, $_, $_, $server] = createServerModel();
    $server->update(['status' => $status?->value]);

    deleteAsAdmin($server, ['no_purge' => true])->assertNoContent();

    expect(Server::find($server->id))->toBeNull();
    Http::assertNothingSent();
    Queue::assertNothingPushed();
})->with([null, Status::DELETING, Status::DELETION_FAILED, Status::INSTALLING]);

it('marks the server deletion_failed when the chain fails', function () {
    fakeVmPresent();
    Queue::fake()->serializeAndRestore();
    [$_, $_, $_, $server] = createServerModel();

    deleteAsAdmin($server)->assertNoContent();

    Queue::pushed(PurgeBackupsJob::class)->first()
         ->invokeChainCatchCallbacks(new RuntimeException('destroy failed'));

    expect($server->fresh()->status)->toBe(Status::DELETION_FAILED->value);
});

it('purges locked backups too when the server is deleted', function () {
    Http::fake(['*' => Http::response(['data' => 'dummy-upid'], 200)]);
    [$_, $_, $_, $server] = createServerModel();
    $backup = Backup::factory()->for($server)->create([
        'is_locked' => true,
        'is_successful' => true,
        'completed_at' => now(),
    ]);

    app(PurgeBackupsService::class)->handle($server, force: true);

    expect(Backup::find($backup->id))->toBeNull();
});

it('sees a VM that answers as existing', function () {
    fakeVmPresent();
    [$_, $_, $_, $server] = createServerModel();

    expect(app(ServerBuildService::class)->vmExists($server))->toBeTrue();
});

it('sees Proxmox "does not exist" as a missing VM', function () {
    fakeVmGone();
    [$_, $_, $_, $server] = createServerModel();

    expect(app(ServerBuildService::class)->vmExists($server))->toBeFalse();
});

it('throws on any other Proxmox failure rather than guess', function () {
    Http::fake(['*' => Http::response(['data' => null, 'message' => 'proxy error'], 503)]);
    [$_, $_, $_, $server] = createServerModel();

    expect(fn () => app(ServerBuildService::class)->vmExists($server))
        ->toThrow(ProxmoxConnectionException::class);
});
