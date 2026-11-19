<?php

use App\Enums\Admin\AdminPermission;
use App\Enums\Api\ApiKeyType;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\PersonalAccessToken;
use App\Models\Server;
use App\Models\SystemActor;
use App\Models\User;
use App\Services\Api\CreateApplicationTokenService;
use App\Services\Users\UserDeletionService;
use Illuminate\Support\Facades\Http;

/**
 * v2 panel-wide (application) tokens: owned by the system actor, not the minting admin, so they
 * survive that admin's deletion.
 */
function mintApplicationToken(User $admin, string $name = 'ci'): PersonalAccessToken
{
    app(CreateApplicationTokenService::class)->handle($admin, $name);

    return PersonalAccessToken::query()->latest('id')->firstOrFail();
}

it('mints an application token owned by the system actor, not the admin', function () {
    $admin = User::factory()->create(['root_admin' => true]);

    $response = $this->actingAs($admin)
        ->postJson('/api/admin/tokens', ['name' => 'deploy-bot'])
        ->assertSuccessful();

    $token = PersonalAccessToken::query()->latest('id')->firstOrFail();

    expect($token->tokenable)->toBeInstanceOf(SystemActor::class)
        ->and($token->type)->toBe(ApiKeyType::APPLICATION)
        ->and($token->created_by)->toBe($admin->id);

    $response->assertJsonPath('data.createdBy.id', $admin->id);
});

it('keeps the token working after the creating admin is deleted', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $plain = app(CreateApplicationTokenService::class)
        ->handle($admin, 'survivor')->plainTextToken;

    app(UserDeletionService::class)->delete($admin);

    $token = PersonalAccessToken::query()->latest('id')->firstOrFail();
    expect($token->fresh()->created_by)->toBeNull(); // audit link nulled, token intact

    // It still authenticates and reaches the admin surface.
    $this->withHeader('Authorization', "Bearer {$plain}")
        ->getJson('/api/application/locations')
        ->assertOk();
});

it('forbids an application token from managing tokens', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $plain = app(CreateApplicationTokenService::class)
        ->handle($admin, 'no-self-manage')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$plain}")
        ->getJson('/api/application/tokens')
        ->assertForbidden();
});

it('reuses the single system actor across tokens', function () {
    $admin = User::factory()->create(['root_admin' => true]);

    mintApplicationToken($admin, 'a');
    mintApplicationToken($admin, 'b');

    expect(SystemActor::query()->count())->toBe(1)
        ->and(PersonalAccessToken::query()->distinct()->pluck('tokenable_id')->all())->toBe([SystemActor::instance()->id]);
});

function applicationBearer(): string
{
    $admin = User::factory()->create(['root_admin' => true]);

    return app(CreateApplicationTokenService::class)->handle($admin, 'writes')->plainTextToken;
}

it('creates a network interface with an application token', function () {
    $node = Node::factory()->for(Location::factory())->create();

    $this->withToken(applicationBearer())
        ->postJson("/api/application/nodes/{$node->id}/network-interfaces", [
            'name' => 'vmbr1',
            'is_vlan_aware' => false,
        ])
        ->assertCreated();
});

it('deletes a network interface with an application token', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $interface = NetworkInterface::factory()->for($node)->create();

    $this->withToken(applicationBearer())
        ->deleteJson("/api/application/nodes/{$node->id}/network-interfaces/{$interface->id}")
        ->assertNoContent();
});

it('attaches and detaches a node with an application token', function () {
    $node = Node::factory()->for(Location::factory())->create();
    $interface = NetworkInterface::factory()->for($node)->create();
    $group = AddressBlockGroup::factory()->create();
    $token = applicationBearer();

    $this->withToken($token)
        ->postJson("/api/application/address-block-groups/{$group->id}/nodes", [
            'network_interface_id' => $interface->id,
        ])
        ->assertCreated();

    $this->withToken($token)
        ->deleteJson("/api/application/address-block-groups/{$group->id}/nodes/{$node->id}")
        ->assertNoContent();

    expect($group->networkInterfaces()->exists())->toBeFalse();
});

it('refuses to detach a node whose servers use the group with an application token', function () {
    [, , $node, $server] = createServerModel();
    $interface = NetworkInterface::factory()->for($node)->create();
    $group = AddressBlockGroup::factory()->create();
    $group->networkInterfaces()->attach($interface);
    $block = AddressBlock::factory()->create(['address_block_group_id' => $group->id]);
    Address::factory()->create(['address_block_id' => $block->id, 'server_id' => $server->id]);

    $this->withToken(applicationBearer())
        ->deleteJson("/api/application/address-block-groups/{$group->id}/nodes/{$node->id}")
        ->assertForbidden();

    expect($group->networkInterfaces()->exists())->toBeTrue();
});

it('deletes an address block and its group with an application token', function () {
    $group = AddressBlockGroup::factory()->create();
    $block = AddressBlock::factory()->create(['address_block_group_id' => $group->id]);
    $token = applicationBearer();

    $this->withToken($token)
        ->deleteJson("/api/application/address-block-groups/{$group->id}/address-blocks/{$block->id}")
        ->assertNoContent();

    $this->withToken($token)
        ->deleteJson("/api/application/address-block-groups/{$group->id}")
        ->assertNoContent();
});

it('creates an admin role with an application token', function () {
    $this->withToken(applicationBearer())
        ->postJson('/api/application/admin-roles', [
            'name' => 'Provisioning',
            'description' => null,
            'permissions' => [AdminPermission::SERVERS_MANAGE->value],
        ])
        ->assertSuccessful();
});

it('sends a power command with an application token', function () {
    fakeProxmox();
    [, , , $server] = createServerModel();

    $this->withToken(applicationBearer())
        ->postJson("/api/application/servers/{$server->uuid}/power", ['command' => 'start'])
        ->assertNoContent();
});

it('keeps an application token out of the client server API', function () {
    fakeProxmox();
    $token = applicationBearer();
    [, , , $server] = createServerModel();

    // The owner shares the system actor's id, which is what used to let the token through.
    $actorId = SystemActor::instance()->id;
    $owner = User::query()->find($actorId) ?? User::factory()->create(['id' => $actorId]);
    Server::query()->whereKey($server->id)->update(['user_id' => $owner->id]);

    $this->withToken($token)
        ->postJson("/api/client/servers/{$server->uuid}/power", ['command' => 'start'])
        ->assertNotFound();

    Http::assertNothingSent();
});
