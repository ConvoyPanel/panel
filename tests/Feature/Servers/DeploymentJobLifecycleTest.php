<?php

use App\Data\Server\Proxmox\ServerStateData;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\DeploymentType;
use App\Enums\Server\State;
use App\Exceptions\Proxmox\RequestException;
use App\Jobs\Server\CloneVmJob;
use App\Jobs\Server\DeleteVmJob;
use App\Jobs\Server\StopVmJob;
use App\Models\Deployment;
use App\Models\Template;
use App\Models\TemplateGroup;
use App\Services\Proxmox\Server\ProxmoxPowerClient;
use App\Services\Proxmox\Server\ProxmoxServerClient;
use App\Services\Servers\ServerBuildService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

/**
 * These jobs each own a step whose work spans many queue invocations: they kick
 * off a Proxmox task once, then re-run (release) polling it to completion. The
 * risk the merge introduces — re-issuing the command on a released run — is what
 * these tests pin down: `build`/`send`/`delete` must be called exactly once
 * across both handle() calls, guarded by the persisted task_upid.
 */
function makeStepFor(string $name): array
{
    [, , , $server] = createServerModel();

    // The clone job reads deployment->template; the others don't need one.
    $templateId = null;
    if ($name === 'clone') {
        $group = TemplateGroup::create(['uuid' => (string) Str::uuid(), 'name' => 'grp', 'order_column' => 1]);
        $templateId = Template::create(['template_group_id' => $group->id, 'name' => 'tpl', 'vmid' => 100])->id;
    }

    $deployment = Deployment::create([
        'server_id' => $server->id,
        'template_id' => $templateId,
        'type' => DeploymentType::INSTALL,
        'status' => DeploymentStatus::PENDING,
        'start_on_completion' => false,
        'requested_at' => now(),
    ]);

    return [$deployment->steps()->create(['name' => $name, 'status' => DeploymentStatus::PENDING]), $server];
}

/** A queue job stub so InteractsWithQueue::release() has something to call. */
function fakeQueueJob(): Job
{
    return Mockery::mock(Job::class)->shouldIgnoreMissing();
}

/** A Proxmox error whose body reads "does not exist", i.e. the VM is gone. */
function nonexistentVmError(): RequestException
{
    return new RequestException(
        new Response(new PsrResponse(500, [], json_encode(['message' => 'VM 100 does not exist']))),
    );
}

it('starts the clone once, then resumes polling the same task to completion', function () {
    [$step] = makeStepFor('clone');

    $service = Mockery::mock(ServerBuildService::class);
    $service->shouldReceive('build')->once()->andReturn('UPID:clone:abc'); // exactly once across both runs
    $service->shouldReceive('getCloneProgress')->andReturn([40, 100]);
    $service->shouldReceive('isVmCreated')->andReturn(false, true);

    $job = (new CloneVmJob($step))->setJob(fakeQueueJob());

    $job->handle($service); // kick + first poll: not created yet → released
    $step->refresh();
    expect($step->task_upid)->toBe('UPID:clone:abc')
        ->and($step->status)->toBe(DeploymentStatus::RUNNING)
        ->and($step->progress_total)->toBe(100)
        ->and($step->progress_current)->toBe(40);

    $job->handle($service); // second poll: created → completed, no rebuild
    expect($step->fresh()->status)->toBe(DeploymentStatus::COMPLETED);
});

it('kills the guest once, then completes when it reports stopped', function () {
    [$step] = makeStepFor('stop-vm');

    $power = Mockery::mock(ProxmoxPowerClient::class);
    $power->shouldReceive('setServer')->andReturnSelf();
    $power->shouldReceive('send')->once()->andReturn('UPID:stop'); // exactly one KILL

    $client = Mockery::mock(ProxmoxServerClient::class);
    $client->shouldReceive('setServer')->andReturnSelf();
    $client->shouldReceive('getState')->andReturn(
        new ServerStateData(State::RUNNING, 0.0, 0, 0, 0),
        new ServerStateData(State::STOPPED, 0.0, 0, 0, 0),
    );

    $job = (new StopVmJob($step))->setJob(fakeQueueJob());

    $job->handle($power, $client); // kill + still running → released
    expect($step->fresh()->task_upid)->toBe('UPID:stop')
        ->and($step->fresh()->status)->toBe(DeploymentStatus::RUNNING);

    $job->handle($power, $client); // stopped → completed
    expect($step->fresh()->status)->toBe(DeploymentStatus::COMPLETED);
});

it('treats an already-gone guest as already stopped', function () {
    [$step] = makeStepFor('stop-vm');

    $power = Mockery::mock(ProxmoxPowerClient::class);
    $power->shouldReceive('setServer')->andReturnSelf();
    $power->shouldReceive('send')->andThrow(nonexistentVmError());

    $job = (new StopVmJob($step))->setJob(fakeQueueJob());
    $job->handle($power, Mockery::mock(ProxmoxServerClient::class));

    expect($step->fresh()->status)->toBe(DeploymentStatus::COMPLETED);
});

it('destroys the guest once, then completes when it is gone', function () {
    [$step] = makeStepFor('delete-vm');

    $service = Mockery::mock(ServerBuildService::class);
    $service->shouldReceive('delete')->once()->andReturn('UPID:del'); // exactly one destroy
    $service->shouldReceive('isVmDeleted')->andReturn(false, true);

    $job = (new DeleteVmJob($step))->setJob(fakeQueueJob());

    $job->handle($service); // destroy + not gone yet → released
    expect($step->fresh()->task_upid)->toBe('UPID:del')
        ->and($step->fresh()->status)->toBe(DeploymentStatus::RUNNING);

    $job->handle($service); // gone → completed
    expect($step->fresh()->status)->toBe(DeploymentStatus::COMPLETED);
});

it('treats an already-gone guest as already deleted', function () {
    [$step] = makeStepFor('delete-vm');

    $service = Mockery::mock(ServerBuildService::class);
    $service->shouldReceive('delete')->andThrow(nonexistentVmError());

    $job = (new DeleteVmJob($step))->setJob(fakeQueueJob());
    $job->handle($service);

    expect($step->fresh()->status)->toBe(DeploymentStatus::COMPLETED);
});
