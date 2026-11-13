<?php

use Convoy\Enums\Server\Status;
use Convoy\Jobs\Server\BuildServerJob;
use Convoy\Jobs\Server\SyncBuildJob;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\Template;
use Convoy\Models\TemplateGroup;
use Convoy\Models\User;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Queue;

function serverPayload(Node $node, User $owner, array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'adopted',
        'user_id' => $owner->id,
        'node_id' => $node->id,
        'vmid' => 4242,
        'hostname' => 'adopted.example.com',
        'limits' => [
            'cpu' => 1,
            'memory' => 1024 * 1024 * 1024,
            'disk' => 10 * 1024 * 1024 * 1024,
            'snapshots' => null,
            'backups' => null,
            'bandwidth' => null,
            'address_ids' => [],
        ],
        'should_create_server' => false,
        'start_on_completion' => false,
    ], $overrides);
}

function createAsAdmin(array $payload)
{
    return test()->actingAs(User::factory()->create(['root_admin' => true]))
        ->postJson('/api/admin/servers', $payload);
}

// "Should Create VM" off: the server takes over a VM that is already on the
// node, so there's no template and no password. This failed with a 500
// ("Attempt to read property name on null") after writing the server.
it('adopts an existing VM without a template or a password', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $owner = User::factory()->create();

    createAsAdmin(serverPayload($node, $owner))->assertOk();

    $server = Server::where('vmid', 4242)->sole();
    expect($server->status)->toBe(Status::INSTALLING->value);
    Queue::assertPushedWithChain(SyncBuildJob::class, [CallQueuedClosure::class]);
    Queue::assertNotPushed(BuildServerJob::class);
});

it('still requires a template and a password to build a new VM', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $owner = User::factory()->create();

    createAsAdmin(serverPayload($node, $owner, ['should_create_server' => true]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['template_uuid', 'account_password']);

    expect(Server::count())->toBe(0);
});

it('builds a new VM from a template', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $owner = User::factory()->create();
    $group = TemplateGroup::create(['node_id' => $node->id, 'name' => 'Linux', 'hidden' => false]);
    $template = Template::create([
        'template_group_id' => $group->id,
        'name' => 'Debian 12',
        'vmid' => 9000,
        'hidden' => false,
    ]);

    createAsAdmin(serverPayload($node, $owner, [
        'should_create_server' => true,
        'template_uuid' => $template->uuid,
        'account_password' => 'Rt-Aa1!bcdefgh9',
    ]))->assertOk();

    Queue::assertPushed(BuildServerJob::class);
});
