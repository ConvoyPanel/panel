<?php

use App\Models\Location;
use App\Models\Node;
use App\Models\Storage;
use App\Rules\HasSufficientDiskSpace;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
    // 100 MiB reserve buffer (passed in bytes; StorageSizeCast stores MiB).
    $this->storage = Storage::factory()->create(['reserved_bytes' => 100 * 1048576]);
    $this->node->storages()->attach($this->storage);
});

/** Fake the live storage status with a given free-space figure (bytes). */
function fakeStorageAvail(string $name, int $avail): void
{
    Http::fake([
        '*/storage' => Http::response([
            'data' => [[
                'storage' => $name,
                'total' => $avail * 2,
                'used' => $avail,
                'avail' => $avail,
                'enabled' => 1,
                'active' => 1,
                'shared' => 0,
                'content' => 'images',
            ]],
        ], 200),
    ]);
}

function runRule(HasSufficientDiskSpace $rule, array $data, mixed $value): array
{
    $rule->setData($data);
    $failures = [];
    $rule->validate('limits.disk', $value, function (string $message) use (&$failures) {
        $failures[] = $message;
    });

    return $failures;
}

it('rejects a disk larger than free-for-Convoy (physicalFree − reserve)', function () {
    // 1 GiB free, 100 MiB reserved ⇒ freeForConvoy ≈ 924 MiB.
    fakeStorageAvail($this->storage->name, 1024 * 1048576);

    $data = ['node_id' => $this->node->id, 'storage_id' => $this->storage->id];
    $free = 1024 * 1048576 - 100 * 1048576;

    // Just over the line → rejected.
    expect(runRule(new HasSufficientDiskSpace, $data, $free + 1))->not->toBeEmpty();
    // Exactly at the line → allowed.
    expect(runRule(new HasSufficientDiskSpace, $data, $free))->toBeEmpty();
});

it('fails open when the node is offline', function () {
    Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('node down'));

    $data = ['node_id' => $this->node->id, 'storage_id' => $this->storage->id];

    // A wildly oversized disk is not rejected when capacity can't be read.
    expect(runRule(new HasSufficientDiskSpace, $data, PHP_INT_MAX))->toBeEmpty();
});
