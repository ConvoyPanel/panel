<?php

use Convoy\Data\Server\Deployments\ServerDeploymentData;
use Convoy\Jobs\Server\MonitorStateJob;
use Convoy\Jobs\Server\SendPowerCommandJob;
use Convoy\Jobs\Server\SyncBuildJob;
use Convoy\Jobs\Server\UpdatePasswordJob;
use Convoy\Jobs\Server\WaitUntilGuestAgentIsReadyJob;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Convoy\Repositories\Proxmox\Server\ProxmoxGuestAgentRepository;
use Convoy\Services\Servers\ServerBuildDispatchService;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

function guestAgentTestTemplate(int $nodeId, string $name): Template
{
    $group = TemplateGroup::create(['node_id' => $nodeId, 'name' => 'Templates', 'hidden' => false]);

    return Template::create([
        'template_group_id' => $group->id,
        'name' => $name,
        'vmid' => 9000,
        'hidden' => false,
    ]);
}

it('waits for the guest agent before setting a Windows password', function () {
    Bus::fake();

    [$_, $_, $node, $server] = createServerModel();

    app(ServerBuildDispatchService::class)->build(ServerDeploymentData::from([
        'server' => $server,
        'template' => guestAgentTestTemplate($node->id, 'Windows Server 2022'),
        'account_password' => 'Rt-Aa1!bcdefgh9',
        'should_create_server' => false,
        'start_on_completion' => true,
    ]));

    Bus::assertChained([
        SyncBuildJob::class,
        SendPowerCommandJob::class,
        MonitorStateJob::class,
        WaitUntilGuestAgentIsReadyJob::class,
        UpdatePasswordJob::class,
        CallQueuedClosure::class,
    ]);
});

it('does not wait for the guest agent on other templates', function () {
    Bus::fake();

    [$_, $_, $node, $server] = createServerModel();

    app(ServerBuildDispatchService::class)->build(ServerDeploymentData::from([
        'server' => $server,
        'template' => guestAgentTestTemplate($node->id, 'Debian 12'),
        'account_password' => 'Rt-Aa1!bcdefgh9',
        'should_create_server' => false,
        'start_on_completion' => true,
    ]));

    Bus::assertChained([
        SyncBuildJob::class,
        UpdatePasswordJob::class,
        SendPowerCommandJob::class,
        CallQueuedClosure::class,
    ]);
});

it('releases itself while the guest agent is not running', function () {
    Http::fake(['*/agent/get-osinfo' => Http::response([
        'data' => null,
        'message' => "QEMU guest agent is not running\n",
    ], 500)]);

    [$_, $_, $_, $server] = createServerModel();

    $job = (new WaitUntilGuestAgentIsReadyJob($server->id))->withFakeQueueInteractions();
    $job->handle(app(ProxmoxGuestAgentRepository::class));

    $job->assertReleased(WaitUntilGuestAgentIsReadyJob::RELEASE_SECONDS);
});

it('lets the chain go on once the guest agent answers', function () {
    Http::fake(['*/agent/get-osinfo' => Http::response([
        'data' => ['result' => ['id' => 'mswindows', 'name' => 'Microsoft Windows']],
    ], 200)]);

    [$_, $_, $_, $server] = createServerModel();

    $job = (new WaitUntilGuestAgentIsReadyJob($server->id))->withFakeQueueInteractions();
    $job->handle(app(ProxmoxGuestAgentRepository::class));

    $job->assertNotReleased()->assertNotFailed();
});

it('lets the chain go on when the guest agent never answers', function () {
    Http::fake(['*/agent/get-osinfo' => Http::response([
        'data' => null,
        'message' => "QEMU guest agent is not running\n",
    ], 500)]);

    [$_, $_, $_, $server] = createServerModel();

    $job = (new WaitUntilGuestAgentIsReadyJob($server->id))->withFakeQueueInteractions();
    $job->job->attempts = $job->tries;
    $job->handle(app(ProxmoxGuestAgentRepository::class));

    $job->assertNotReleased()->assertNotFailed();
});
