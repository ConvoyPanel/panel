<?php

use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
    DatabaseTransactions::class,
    // Illuminate\Foundation\Testing\RefreshDatabase::class,
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
            'cpu' => 2,
            'memory' => 2048 * 1024 * 1024,
            'disk' => 20 * 1024 * 1024 * 1024,
            'backup_limit' => 16,
            'snapshot_limit' => 16,
            'bandwidth_limit' => 100 * 1024 * 1024 * 1024,
        ];
    });

    return [
        $user,
        $location,
        $node,
        $server,
    ];
}
