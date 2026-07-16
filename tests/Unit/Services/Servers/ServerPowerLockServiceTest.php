<?php

use App\Enums\Server\PowerCommand;
use App\Exceptions\Http\Server\PowerActionInProgressException;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxPowerClient;
use App\Services\Servers\Power\ServerPowerLockService;
use App\Services\Servers\SendServerPowerCommand;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    // A bare model is enough — the lock keys only off $server->id, no DB needed.
    $this->server = tap(new Server, fn (Server $s) => $s->id = 4242);
    $this->lock = new ServerPowerLockService;
});

it('records the pending action when the lock is acquired', function () {
    expect($this->lock->pending($this->server))->toBeNull();

    $pending = $this->lock->acquire($this->server, PowerCommand::RESTART);

    expect($pending->command)->toBe(PowerCommand::RESTART)
        ->and($this->lock->pending($this->server)->command)->toBe(PowerCommand::RESTART);
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
