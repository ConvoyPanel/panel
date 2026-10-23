<?php

use Convoy\Jobs\Telemetry\SendTelemetryReportJob;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;

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

// RefreshDatabase on a database of its own (tests/bootstrap.php), not transactions
// on the dev database: Postgres sequences advance even when a transaction rolls
// back, and dev data showed up in every fleet-wide count the suite asserts on.
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
    $service = app(Convoy\Services\Servers\ServerCreationService::class);
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

/**
 * Runs the daily telemetry job here and now. dispatchSync() would hand it to
 * the faked queue instead.
 */
function runTelemetryReportJob(): void
{
    app()->call([new SendTelemetryReportJob, 'handle']);
}

/**
 * The console's rules for a report (StoreTelemetryReportRequest and its SlugMap
 * rule), applied to the report as it goes over the wire.
 */
function validateTelemetryReport(array $report): Illuminate\Validation\Validator
{
    $count = ['required', 'integer', 'min:0', 'max:2000000000'];
    $version = ['string', 'max:64', 'regex:/^v?(\d+(?:\.\d+){1,3})/'];
    $slugMap = fn (bool $booleans = false) => function (string $attribute, mixed $value, Closure $fail) use ($booleans) {
        if (! is_array($value) || ($value !== [] && array_is_list($value)) || count($value) > 500) {
            $fail("{$attribute} must be an object of at most 500 keys.");

            return;
        }

        foreach ($value as $key => $item) {
            if (! is_string($key) || strlen($key) > 120 || preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $key) !== 1) {
                $fail("{$attribute} has a key that is not a slug: {$key}");
            }

            if ($booleans ? ! is_bool($item) : (! is_int($item) || $item < 0 || $item > 2000000000)) {
                $fail("{$attribute}.{$key} is not a ".($booleans ? 'boolean' : 'count'));
            }
        }
    };

    return Validator::make(json_decode(json_encode($report), true), [
        'schema' => ['required', 'integer', 'in:1'],
        'installation' => ['required', 'uuid'],
        'panel' => ['required', 'array'],
        'panel.version' => ['required', 'string', 'max:64'],
        'panel.php' => ['required', ...$version],
        'panel.database' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9-]+$/'],
        'panel.database_version' => ['nullable', ...$version],
        'users' => ['required', 'array'],
        'users.total' => $count,
        'users.admins' => $count,
        'users.active.day' => $count,
        'users.active.week' => $count,
        'users.active.month' => $count,
        'users.active_admins.day' => $count,
        'users.active_admins.week' => $count,
        'users.active_admins.month' => $count,
        'nodes' => ['present', 'list', 'max:1000'],
        'nodes.*.proxmox' => ['nullable', ...$version],
        'nodes.*.agent' => ['nullable', ...$version],
        'nodes.*.cpus' => ['required', 'integer', 'min:0', 'max:100000'],
        'nodes.*.memory_gib' => ['required', 'integer', 'min:0', 'max:1000000'],
        'nodes.*.servers' => $count,
        'resources' => ['sometimes', $slugMap()],
        'features' => ['sometimes', $slugMap(booleans: true)],
        'activity' => ['sometimes', $slugMap()],
        'views' => ['sometimes', $slugMap()],
    ]);
}
