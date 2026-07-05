<?php

use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(
    Tests\TestCase::class,
    RefreshDatabase::class,
)->beforeEach(function () {
    Http::preventStrayRequests();
    Queue::fake();
})->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

//expect()->extend('toBeOne', function () {
//    return $this->toBe(1);
//});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createServerModel(): array
{
    $location = Location::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();
    /** @var Node $node */
    $node = Node::factory()->for($location)->create();
    $service = app(App\Services\Servers\ServerCreationService::class);
    /** @var Server $server */
    $server = Server::factory()->create(function () use ($user, $node, $service) {
        $uuid = $service->generateUniqueUuidCombo();

        return [
            'uuid' => $uuid,
            'uuid_short' => substr($uuid, 0, 8),
            'user_id' => $user,
            'node_id' => $node,
        ];
    });

    // Make the server's storage available on its node (backup/iso operations
    // resolve a capability-matched storage from the node).
    $node->storages()->attach($server->storage);

    return [
        $user,
        $location,
        $node,
        $server,
    ];
}

/**
 * The Proxmox VM config fixture as a decoded array, optionally merged with extra
 * top-level keys (e.g. an `ide3` line to simulate a mounted ISO).
 *
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function serverConfigFixture(array $extra = []): array
{
    $config = json_decode(
        file_get_contents(base_path('tests/Fixtures/Repositories/Server/GetServerConfigData.json')),
        true,
    );
    $config['data'] = array_merge($config['data'], $extra);

    return $config;
}

/**
 * Fake the Proxmox HTTP API: every `.../config` read returns the config fixture,
 * everything else returns a dummy task upid. Pass $overrides to add or replace
 * specific URL patterns; they are matched first (Http::fake is first-match-wins)
 * and win over the defaults on a key collision.
 *
 * @param  array<string, mixed>  $overrides
 */
function fakeProxmox(array $overrides = []): void
{
    Http::fake($overrides + [
        '*/config' => Http::response(serverConfigFixture(), 200),
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);
}
