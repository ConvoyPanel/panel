<?php

use Convoy\Models\ISO;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Services\Isos\IsoMonitorService;
use Illuminate\Support\Facades\Http;

// The size recorded when a download starts comes from the URL's metadata,
// before anything is downloaded. Once the node has the file, its own size wins.
function pendingIso(): ISO
{
    $node = Node::factory()->for(Location::factory())->create();

    return ISO::factory()->for($node)->create([
        'file_name' => 'netboot.iso',
        'size' => 2 * 1024 * 1024,
        'is_successful' => false,
        'completed_at' => null,
    ]);
}

it('records the size of the file the node saved', function () {
    $iso = pendingIso();
    Http::fake([
        '*/tasks/*' => Http::response(['data' => ['status' => 'stopped', 'exitstatus' => 'OK']]),
        '*/content*' => Http::response(['data' => [
            ['volid' => 'local:iso/netboot.iso', 'size' => 3_092_480, 'ctime' => 1_700_000_000],
        ]]),
    ]);

    app(IsoMonitorService::class)->checkDownloadProgress($iso, 'UPID:test');

    expect($iso->fresh())
        ->is_successful->toBeTrue()
        ->size->toBe(3_092_480);
});

it('keeps the recorded size when the node cannot list its ISOs', function () {
    $iso = pendingIso();
    Http::fake([
        '*/tasks/*' => Http::response(['data' => ['status' => 'stopped', 'exitstatus' => 'OK']]),
        '*/content*' => Http::response(['data' => null, 'message' => 'storage offline'], 500),
    ]);

    app(IsoMonitorService::class)->checkDownloadProgress($iso, 'UPID:test');

    expect($iso->fresh())
        ->is_successful->toBeTrue()
        ->size->toBe(2 * 1024 * 1024);
});
