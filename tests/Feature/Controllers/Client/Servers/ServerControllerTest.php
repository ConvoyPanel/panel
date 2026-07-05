<?php

use App\Models\User;

it('only lists servers the authenticated user owns', function () {
    [$owner, $_, $_, $server] = createServerModel();

    $otherUser = User::factory()->create();

    $this->actingAs($otherUser)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(0, 'items');

    $this->actingAs($owner)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.uuid', $server->uuid);
});

it('does not list other users servers for root admins', function () {
    // The client area is owner-scoped even for admins; they must not see
    // servers they do not own here (the admin area is for that).
    [$_owner, $_, $_, $_server] = createServerModel();

    $admin = User::factory()->create(['root_admin' => true]);

    $this->actingAs($admin)
        ->getJson('/api/client/servers')
        ->assertOk()
        ->assertJsonCount(0, 'items');
});

it('can generate noVNC authorization token', function () {
    Http::fake([
        '*/api2/json/access/users' => Http::response(
            file_get_contents(base_path('tests/Fixtures/Repositories/Node/CreateUserData.json')),
            200,
        ),
        '*/api2/json/access/roles' => Http::response(
            file_get_contents(base_path('tests/Fixtures/Repositories/Node/CreateRoleData.json')),
            200,
        ),
        '*/api2/json/access/acl' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Server/AddUserToServerData.json'),
            ), 200,
        ),
        '*/api2/json/access/ticket' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Node/CreateUserTicketData.json'),
            ), 200,
        ),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/create-console-session", [
            'type' => 'novnc',
        ],
    );

    $response->assertCreated();
});
