<?php

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

    $response->assertOk();
});
