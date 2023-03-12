<?php

use Convoy\Models\ISO;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Http::preventStrayRequests());

it('can rename servers', function () {
    Http::fake([
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/rename", [
        'name' => 'advinservers is king',
        'hostname' => 'advinservers.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'advinservers is king')
        ->assertJsonPath('data.hostname', 'advinservers.com');
});

it('can change server passwords', function () {
    Http::fake([
        '*/pending' => Http::response(file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetServerPendingConfigData.json')), 200),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->putJson("/api/client/servers/{$server->uuid}/settings/security", [
        'type' => 'cipassword',
        'password' => 'Advinservers is king!123',
    ]);

    $response->assertStatus(204);
});

it('can mount visible ISOs', function () {
    Http::fake([
        '*/pending' => Http::response(file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetServerPendingConfigData.json')), 200),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $iso = ISO::factory()->create([
        'node_id' => $server->node_id,
        'hidden' => false,
    ]);

    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/hardware/isos/{$iso->uuid}/mount");

    $response->assertStatus(204);
});

it('can\'t mount hidden ISOs as non-admin user', function () {
    [$user, $_, $_, $server] = createServerModel();

    $iso = ISO::factory()->create([
        'node_id' => $server->node_id,
        'hidden' => true,
    ]);

    $response = $this->actingAs($user)->postJson("/api/client/servers/{$server->uuid}/settings/hardware/isos/{$iso->uuid}/mount");

    $response->assertStatus(403);
});
