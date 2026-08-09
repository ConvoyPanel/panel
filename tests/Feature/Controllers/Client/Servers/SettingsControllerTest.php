<?php

use Convoy\Models\ISO;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Illuminate\Support\Facades\Http;

it('can rename servers', function () {
    Http::fake([
        '*/config' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json'),
            ), 200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/rename", [
        'name' => 'advinservers is king',
        'hostname' => 'advinservers.com',
    ],
    );

    $response->assertOk()
             ->assertJsonPath('data.name', 'advinservers is king')
             ->assertJsonPath('data.hostname', 'advinservers.com');
});

it('can change nameservers', function () {
    Http::fake([
        '*/config' => Http::response(
            file_get_contents(
                base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json'),
            ), 200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->putJson(
        "/api/client/servers/{$server->uuid}/settings/network", [
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
            ), 200,
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
            ), 200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->putJson(
        "/api/client/servers/{$server->uuid}/settings/auth", [
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
            ), 200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    ISO::factory()->count(10)->create([
        'node_id' => $server->node_id,
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
            ), 200,
        ),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $iso = ISO::factory()->create([
        'node_id' => $server->node_id,
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
        'node_id' => $server->node_id,
        'hidden' => true,
    ]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$iso->uuid}/mount",
    );

    $response->assertStatus(403);
});

/*
 * Neither mountMedia() nor unmountMedia() compares the ISO's node to the
 * server's: MountMediaRequest only inspects the hidden flag, unmountMedia has
 * no form request at all, and AllocationService builds the drive from the
 * caller's node storage and the ISO's file name without re-checking either.
 * Server::isos() bridging Node's ISOs onto the server's node_id is what lets
 * the scoped binding resolve {iso} and 404 a foreign one, so these assert the
 * 404 *and* that nothing reached Proxmox.
 */
describe('ISOs on another node', function () {
    beforeEach(function () {
        // The same fake the passing mount case uses, so a foreign ISO that got
        // through would mount cleanly and return 204 rather than erroring on a
        // thin stub. The 404 has to come from the scoping, not from luck.
        Http::fake([
            '*/config' => Http::response(
                file_get_contents(
                    base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json'),
                ), 200,
            ),
            '*' => Http::response(['data' => 'dummy-upid'], 200),
        ]);

        // Visible and complete, so a pass can only mean the ISO was rejected on
        // its node rather than by the hidden check or the is_successful filter.
        $this->foreignIso = ISO::factory()
            ->for(Node::factory()->for(Location::factory()))
            ->create([
                'hidden' => false,
                'is_successful' => true,
            ]);
    });

    it("can't mount an ISO belonging to another node", function () {
        [$user, $_, $_, $server] = createServerModel();

        $response = $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$this->foreignIso->uuid}/mount",
        );

        $response->assertNotFound();

        Http::assertNothingSent();
    });

    it("can't unmount an ISO belonging to another node", function () {
        [$user, $_, $_, $server] = createServerModel();

        $response = $this->actingAs($user)->postJson(
            "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$this->foreignIso->uuid}/unmount",
        );

        $response->assertNotFound();

        Http::assertNothingSent();
    });

    it('leaves it out of the ISO listing', function () {
        [$user, $_, $_, $server] = createServerModel();

        $response = $this->actingAs($user)->getJson(
            "/api/client/servers/{$server->uuid}/settings/hardware/isos",
        );

        $response->assertOk()
                 ->assertJsonMissing(['uuid' => $this->foreignIso->uuid]);
    });
});
