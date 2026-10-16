<?php

use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Enums\Server\Status;
use Convoy\Jobs\Server\BuildServerJob;
use Convoy\Jobs\Server\SendPowerCommandJob;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Convoy\Services\Servers\ServerBuildDispatchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function createTemplateFor(Node $node): Template
{
    $group = TemplateGroup::create(['node_id' => $node->id, 'name' => 'Linux', 'hidden' => false]);

    return Template::create([
        'template_group_id' => $group->id,
        'name' => 'Debian 12',
        'vmid' => 9000,
        'hidden' => false,
    ]);
}

function fakeMissingVm(): void
{
    Http::fake([
        '*/config' => Http::response([
            'data' => null,
            'message' => "Configuration file 'nodes/proxmox/qemu-server/100.conf' does not exist\n",
        ], 500),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);
}

it('retries a failed install straight into a build when its VM is gone', function () {
    fakeMissingVm();

    [$user, $_, $node, $server] = createServerModel();
    $server->update(['status' => Status::INSTALL_FAILED->value]);
    $template = createTemplateFor($node);

    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/reinstall", [
        'template_uuid' => $template->uuid,
        'account_password' => 'Rt-Aa1!bcdefgh9',
        'start_on_completion' => false,
    ])->assertNoContent();

    expect($server->fresh()->status)->toBe(Status::INSTALLING->value);
    // Stopping and deleting a VM that isn't there fails on Proxmox, so the
    // chain has to start at the build.
    Queue::assertPushed(BuildServerJob::class);
    Queue::assertNotPushed(SendPowerCommandJob::class);
});

it('still stops and deletes the old VM first when it exists', function () {
    Http::fake([
        '*/config' => Http::response(
            file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json')),
            200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $node, $server] = createServerModel();
    $template = createTemplateFor($node);

    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/reinstall", [
        'template_uuid' => $template->uuid,
        'account_password' => 'Rt-Aa1!bcdefgh9',
        'start_on_completion' => false,
    ])->assertNoContent();

    Queue::assertPushed(SendPowerCommandJob::class);
    Queue::assertNotPushed(BuildServerJob::class);
});

it('marks a retried install failed when its chain fails', function () {
    fakeMissingVm();
    // Serialize pushed jobs as the real queue does. The failure handler then
    // runs against what was captured at dispatch -- a server that already read
    // install_failed -- which is what used to leave it stuck on "installing".
    Queue::fake()->serializeAndRestore();

    [$_, $_, $node, $server] = createServerModel();
    $server->update(['status' => Status::INSTALL_FAILED->value]);

    app(ServerBuildDispatchService::class)->rebuild(ServerDeploymentData::from([
        'server' => $server,
        'template' => createTemplateFor($node),
        'account_password' => 'Rt-Aa1!bcdefgh9',
        'should_create_server' => true,
        'start_on_completion' => false,
    ]));

    expect($server->fresh()->status)->toBe(Status::INSTALLING->value);

    Queue::pushed(BuildServerJob::class)->first()
         ->invokeChainCatchCallbacks(new RuntimeException('clone failed'));

    expect(Server::find($server->id)->status)->toBe(Status::INSTALL_FAILED->value);
});

it('keeps reinstall blocked for servers in any other state', function () {
    fakeMissingVm();

    [$user, $_, $node, $server] = createServerModel();
    $server->update(['status' => Status::SUSPENDED->value]);

    $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/reinstall", [
        'template_uuid' => createTemplateFor($node)->uuid,
        'account_password' => 'Rt-Aa1!bcdefgh9',
        'start_on_completion' => false,
    ])->assertConflict();

    Queue::assertNothingPushed();
});
