<?php

use App\Models\ISO;
use Illuminate\Support\Facades\Http;

it('can rename servers', function () {
    Http::fake([
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/rename",
        [
            'name' => 'advinservers is king',
            'hostname' => 'advinservers.com',
        ],
    );

    $response->assertCreated()
             ->assertJsonPath('data.name', 'advinservers is king')
             ->assertJsonPath('data.hostname', 'advinservers.com');
});

it('can change nameservers', function () {
    Http::fake([
        '*/config' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json'),
            ),
            200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->putJson(
        "/api/client/servers/{$server->uuid}/settings/network",
        [
            'nameservers' => [
                '1.1.1.1',
                '1.0.0.1',
            ],
        ],
    );

    $response->assertOk();
});

it('can fetch sshkeys', function () {
    Http::fake([
        '*/config' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json'),
            ),
            200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->getJson(
        "/api/client/servers/{$server->uuid}/settings/auth",
    );

    $response->assertOk();
});

it('can change server passwords', function () {
    Http::fake([
        '*/config' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json'),
            ),
            200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->putJson(
        "/api/client/servers/{$server->uuid}/settings/auth",
        [
            'type' => 'password',
            'password' => 'Advinservers is king!123',
        ],
    );

    $response->assertNoContent();
});

it('can fetch available ISOs', function () {
    Http::fake([
        '*/config' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json'),
            ),
            200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    ISO::factory()->count(10)->create([
        'storage_id' => $server->storage_id,
        'hidden' => false,
    ]);

    $response = $this->actingAs($user)->getJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos",
    );

    $response->assertOk();
});

it('can mount visible ISOs', function () {
    Http::fake([
        '*/config' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json'),
            ),
            200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $iso = ISO::factory()->create([
        'storage_id' => $server->storage_id,
        'hidden' => false,
    ]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$iso->uuid}/mount",
    );

    $response->assertNoContent();
});

it('can\'t mount hidden ISOs as non-admin user', function () {
    [$user, $_, $_, $server] = createServerModel();

    $iso = ISO::factory()->create([
        'storage_id' => $server->storage_id,
        'hidden' => true,
    ]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$iso->uuid}/mount",
    );

    $response->assertStatus(403);
});

it('can\'t mount an ISO from a node the user\'s server is not on', function () {
    Http::fake(['*' => Http::response(['data' => 'dummy-upid'], 200)]);

    [$user, $_, $_, $server] = createServerModel();

    // Default factory ISO lives on its own storage, which is NOT attached to
    // the server's node — so it is outside what getMedia would ever list.
    $foreignIso = ISO::factory()->create(['hidden' => false]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$foreignIso->uuid}/mount",
    );

    $response->assertStatus(403);
});

it('can\'t unmount an ISO from a node the user\'s server is not on', function () {
    Http::fake(['*' => Http::response(['data' => 'dummy-upid'], 200)]);

    [$user, $_, $_, $server] = createServerModel();

    $foreignIso = ISO::factory()->create(['hidden' => false]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$foreignIso->uuid}/unmount",
    );

    $response->assertStatus(403);
});
