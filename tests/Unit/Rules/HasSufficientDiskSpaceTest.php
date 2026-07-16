<?php

use App\Models\Location;
use App\Models\Node;
use App\Models\Storage;
use App\Rules\HasSufficientDiskSpace;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
    // 100 MiB reserve buffer (passed in bytes; StorageSizeCast stores MiB).
    $this->storageA = Storage::factory()->create(['reserved_bytes' => 100 * 1048576]);
    $this->storageB = Storage::factory()->create();
    $this->node->storages()->attach($this->storageA);
    $this->node->storages()->attach($this->storageB);
});

/** Fake the live storage status for one or more storages (name => free bytes). */
function fakeStorageAvails(array $availByName): void
{
    $data = [];
    foreach ($availByName as $name => $avail) {
        $data[] = [
            'storage' => $name,
            'total' => $avail * 2,
            'used' => $avail,
            'avail' => $avail,
            'enabled' => 1,
            'active' => 1,
            'shared' => 0,
            'content' => 'images',
        ];
    }

    Http::fake(['*/storage' => Http::response(['data' => $data], 200)]);
}

function runRule(array $data): array
{
    $rule = (new HasSufficientDiskSpace)->setData($data);
    $failures = [];
    $rule->validate('limits.disk', $data['limits']['disk'] ?? 0, function (string $message) use (&$failures) {
        $failures[] = $message;
    });

    return $failures;
}

it('rejects a primary disk larger than free-for-Convoy (physicalFree − reserve)', function () {
    fakeStorageAvails([$this->storageA->name => 1024 * 1048576]);
    $free = 1024 * 1048576 - 100 * 1048576;

    $base = ['node_id' => $this->node->id, 'storage_id' => $this->storageA->id];

    expect(runRule($base + ['limits' => ['disk' => $free + 1]]))->not->toBeEmpty();
    expect(runRule($base + ['limits' => ['disk' => $free]]))->toBeEmpty();
});

it('sums the primary and a secondary disk on the SAME storage', function () {
    fakeStorageAvails([$this->storageA->name => 1000 * 1048576]);
    $free = 1000 * 1048576 - 100 * 1048576; // 900 MiB

    // Each disk fits alone, but together they exceed free-for-Convoy.
    $data = [
        'node_id' => $this->node->id,
        'storage_id' => $this->storageA->id,
        'limits' => [
            'disk' => 600 * 1048576,
            'disks' => [
                ['storage_id' => $this->storageA->id, 'size' => 500 * 1048576],
            ],
        ],
    ];

    $failures = runRule($data);
    expect($failures)->not->toBeEmpty();
    expect($failures[0])->toContain($this->storageA->name);
});

it('checks a secondary disk against its own (different) storage', function () {
    // A has room; B does not.
    fakeStorageAvails([
        $this->storageA->name => 1000 * 1048576,
        $this->storageB->name => 10 * 1048576,
    ]);

    $data = [
        'node_id' => $this->node->id,
        'storage_id' => $this->storageA->id,
        'limits' => [
            'disk' => 100 * 1048576,
            'disks' => [
                ['storage_id' => $this->storageB->id, 'size' => 50 * 1048576],
            ],
        ],
    ];

    $failures = runRule($data);
    expect($failures)->not->toBeEmpty();
    expect($failures[0])->toContain($this->storageB->name);
});

it('fails open when the node is offline', function () {
    Http::fake(fn () => throw new ConnectionException('node down'));

    $data = [
        'node_id' => $this->node->id,
        'storage_id' => $this->storageA->id,
        'limits' => ['disk' => PHP_INT_MAX],
    ];

    expect(runRule($data))->toBeEmpty();
});
