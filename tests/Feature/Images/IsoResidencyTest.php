<?php

use App\Models\ISO;
use App\Models\Node;
use App\Models\Storage;
use App\Services\Isos\IsoResidencyService;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

function isoStorageOn(Node $node): Storage
{
    $node->storages()->update(['stores_iso' => false]);

    $storage = Storage::factory()->create(['name' => 'local', 'stores_iso' => true]);
    $node->storages()->attach($storage);

    return $storage;
}

/** PVE's ISO content listing, plus a stub for the download it may start. */
function fakeIsoContent(array $volids, ?callable $onDownload = null): void
{
    Http::fake([
        '*/storage/*/content*' => Http::response([
            'data' => array_map(fn (string $volid) => ['volid' => $volid], $volids),
        ]),
        '*/download-url' => function ($request) use ($onDownload) {
            $onDownload && $onDownload($request);

            return Http::response(['data' => 'UPID:download']);
        },
    ]);
}

it('fetches an ISO the node has never seen', function () {
    [, , $node] = createServerModel();
    isoStorageOn($node);

    $sent = null;
    fakeIsoContent([], function ($request) use (&$sent) {
        $sent = $request->data();
    });

    $iso = ISO::factory()->create([
        'file_name' => 'debian-12.iso',
        'url' => 'https://example.invalid/debian-12.iso',
        'sha256' => str_repeat('c', 64),
    ]);

    expect(app(IsoResidencyService::class)->ensureResident($node, $iso))->not->toBeNull()
        ->and($sent['content'])->toBe('iso')
        ->and($sent['filename'])->toBe('debian-12.iso')
        // Proxmox verifies it, so the panel never has to trust the origin.
        ->and($sent['checksum'])->toBe(str_repeat('c', 64))
        ->and($sent['checksum-algorithm'])->toBe('sha256');
});

it('does nothing when the node already has the file', function () {
    [, , $node] = createServerModel();
    $storage = isoStorageOn($node);

    fakeIsoContent(["{$storage->name}:iso/debian-12.iso"]);

    $iso = ISO::factory()->create(['file_name' => 'debian-12.iso']);
    $service = app(IsoResidencyService::class);

    expect($service->isResident($node, $iso))->toBeTrue()
        ->and($service->ensureResident($node, $iso))->toBeNull();
});

it('sends no checksum when the library has no hash', function () {
    // A hash is optional -- an operator may only have a link -- and sending an
    // empty one would make Proxmox reject a download that would have worked.
    [, , $node] = createServerModel();
    isoStorageOn($node);

    $sent = null;
    fakeIsoContent([], function ($request) use (&$sent) {
        $sent = $request->data();
    });

    app(IsoResidencyService::class)->ensureResident(
        $node,
        ISO::factory()->create(['sha256' => null]),
    );

    expect($sent)->not->toHaveKey('checksum');
});

it('names the volume a mount will point at', function () {
    [, , $node] = createServerModel();
    $storage = isoStorageOn($node);

    $iso = ISO::factory()->create(['file_name' => 'debian-12.iso']);

    expect(app(IsoResidencyService::class)->volume($node, $iso))
        ->toBe("{$storage->name}:iso/debian-12.iso");
});

it('refuses when the node has nowhere to put an ISO', function () {
    [, , $node] = createServerModel();
    Storage::query()->update(['stores_iso' => false]);

    expect(fn () => app(IsoResidencyService::class)->volume($node, ISO::factory()->create()))
        ->toThrow(ConflictHttpException::class, 'accepts ISOs');
});
