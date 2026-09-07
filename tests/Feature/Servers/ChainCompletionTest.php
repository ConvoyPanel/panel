<?php

use App\Actions\Server\BuildServerAction;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ProgressMode;
use App\Enums\Server\ServerLifecycle;
use App\Jobs\Server\SendPowerCommandJob;
use App\Models\Deployment;
use App\Services\Proxmox\Server\ProxmoxPowerClient;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

it('flips the server to ready once the chain finishes', function () {
    app()->register(QueueServiceProvider::class, true);
    Queue::clearResolvedInstance('queue');
    config()->set('queue.default', 'sync');

    [, , , $server] = createServerModel();

    $deployment = Deployment::create([
        'server_id' => $server->id,
        'template_id' => null,
        'type' => DeploymentType::IMPORT,
        'status' => DeploymentStatus::PENDING,
        'start_on_completion' => true,
        'requested_at' => now(),
    ]);

    $power = Mockery::mock(ProxmoxPowerClient::class);
    $power->shouldReceive('setServer')->andReturnSelf();
    $power->shouldReceive('send')->andReturn('UPID:start');
    app()->instance(ProxmoxPowerClient::class, $power);

    $step = $deployment->addSteps([[
        'name' => 'start-vm',
        'status' => DeploymentStatus::PENDING,
        'progress_mode' => ProgressMode::INDETERMINATE,
    ]])[0];

    $action = app(BuildServerAction::class);
    $call = function (string $method, ...$args) use ($action) {
        $r = new ReflectionMethod($action, $method);

        return $r->invoke($action, ...$args);
    };

    $jobs = Arr::flatten([
        $call('onStart', $deployment),
        [new SendPowerCommandJob($step, PowerCommand::START)],
        $call('onComplete', $deployment),
    ]);

    $server->update(['lifecycle' => ServerLifecycle::INSTALLING]);

    Bus::chain($jobs)->catch($call('onFail', $deployment))->dispatch();

    dump([
        'lifecycle' => $server->fresh()->lifecycle->value,
        'deployment' => $deployment->fresh()->status->value,
        'step' => $step->fresh()->status->value,
    ]);

    expect($server->fresh()->lifecycle)->toBe(ServerLifecycle::READY);
});
