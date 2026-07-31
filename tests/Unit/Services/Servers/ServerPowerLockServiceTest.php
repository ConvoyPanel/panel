<?php

use App\Data\Server\Power\PowerActionResultData;
use App\Enums\Server\PowerCommand;
use App\Exceptions\Http\Server\PowerActionInProgressException;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxActivityClient;
use App\Services\Proxmox\Server\ProxmoxPowerClient;
use App\Services\Servers\Power\ServerPowerLockService;
use App\Services\Servers\SendServerPowerCommand;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    // A bare model is enough — the lock keys only off $server->id, no DB needed.
    $this->server = tap(new Server, fn (Server $s) => $s->id = 4242);
    // These cases exercise acquire/pending/release/resolve's no-task branch and
    // never reach Proxmox, so a bare mock is enough to construct the service.
    // Task-status polling is covered end-to-end in the feature test.
    $this->activity = Mockery::mock(ProxmoxActivityClient::class);
    $this->lock = new ServerPowerLockService($this->activity);
});

it('records the pending action when the lock is acquired', function () {
    expect($this->lock->pending($this->server))->toBeNull();

    $pending = $this->lock->acquire($this->server, PowerCommand::RESTART);

    expect($pending->command)->toBe(PowerCommand::RESTART)
        ->and($this->lock->pending($this->server)->command)->toBe(PowerCommand::RESTART);
});

it('keeps the action pending until a task has been attached', function () {
    // Between acquire() and attachTask() there is no UPID to poll, so resolve()
    // must leave the lock in place — never clear it — until the command's task
    // is on record. The activity client is never touched in this window.
    $this->lock->acquire($this->server, PowerCommand::RESTART);

    expect($this->lock->resolve($this->server)?->command)->toBe(PowerCommand::RESTART);
});

it('rejects a second acquire while the lock is held', function () {
    $this->lock->acquire($this->server, PowerCommand::START);

    expect(fn () => $this->lock->acquire($this->server, PowerCommand::SHUTDOWN))
        ->toThrow(PowerActionInProgressException::class);
});

it('can be re-acquired after release', function () {
    $this->lock->acquire($this->server, PowerCommand::START);
    $this->lock->release($this->server);

    expect($this->lock->pending($this->server))->toBeNull()
        ->and($this->lock->acquire($this->server, PowerCommand::SHUTDOWN)->command)
        ->toBe(PowerCommand::SHUTDOWN);
});

it('drops a previous action result when a new action is acquired', function () {
    // A finished action left its outcome behind; starting a fresh action must
    // clear it so a stale result can't sit alongside the one now in flight.
    $stale = new PowerActionResultData(PowerCommand::SHUTDOWN, now()->toIso8601String(), true, 'OK');
    Cache::put($this->lock->resultKey($this->server), $stale->toArray(), 30);

    expect($this->lock->result($this->server))->not->toBeNull();

    $this->lock->acquire($this->server, PowerCommand::START);

    expect($this->lock->result($this->server))->toBeNull();
});

it('releases the lock when the Proxmox command fails, so it can be retried', function () {
    $client = Mockery::mock(ProxmoxPowerClient::class);
    $client->shouldReceive('setServer')->andReturnSelf();
    $client->shouldReceive('send')->once()->andThrow(new RuntimeException('proxmox down'));

    $action = new SendServerPowerCommand($client, $this->lock);

    expect(fn () => $action->handle($this->server, PowerCommand::START))
        ->toThrow(RuntimeException::class);

    // The failed command must not leave a stuck lock behind.
    expect($this->lock->pending($this->server))->toBeNull();
});
