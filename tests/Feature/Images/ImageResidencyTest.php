<?php

use App\Enums\Image\ImageDiskRole;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Models\ImageVersion;
use App\Models\Storage;
use App\Services\Images\ImageResidencyService;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

const SYSTEM_SHA = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

function makeVersionFor(): ImageVersion
{
    $group = ImageGroup::create(['name' => 'Ubuntu']);
    $definition = ImageDefinition::create([
        'image_group_id' => $group->id,
        'name' => 'Ubuntu 24.04',
        'ostype' => 'l26',
    ]);

    return $definition->versions()->create([
        'version' => '1.0.0',
        'disks' => [[
            'slot' => 'scsi0',
            'role' => ImageDiskRole::SYSTEM->value,
            'url' => 'https://example.invalid/disk.qcow2',
            'path' => null,
            'sha256' => SYSTEM_SHA,
            'size' => 1024,
            'virtual_size' => 2048,
            'format' => 'qcow2',
        ]],
    ]);
}

/** PVE's content listing for the import storage. */
function fakeImportContent(array $volids, ?callable $onDownload = null): void
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

it('downloads a disk the node does not have', function () {
    [, , $node, $server] = createServerModel();
    $node->storages()->first()->update(['stores_import' => true]);

    $sent = null;
    fakeImportContent([], function ($request) use (&$sent) {
        $sent = $request->data();
    });

    $upids = app(ImageResidencyService::class)->ensureResident($node, makeVersionFor());

    expect($upids)->toHaveCount(1)
        // Proxmox verifies the hash itself, which keeps several gigabytes out
        // of the panel and makes a substituted file the node's refusal.
        ->and($sent['checksum'])->toBe(SYSTEM_SHA)
        ->and($sent['checksum-algorithm'])->toBe('sha256')
        ->and($sent['content'])->toBe('import')
        ->and($sent['filename'])->toBe('image-'.SYSTEM_SHA.'.qcow2');
});

it('does nothing when the node already holds that exact build', function () {
    [, , $node] = createServerModel();
    $storage = $node->storages()->first();
    $storage->update(['stores_import' => true]);

    // Named after the hash, so "do I have this?" is answerable by name alone.
    fakeImportContent(["{$storage->name}:import/image-".SYSTEM_SHA.'.qcow2']);

    $version = makeVersionFor();
    $service = app(ImageResidencyService::class);

    expect($service->isResident($node, $version))->toBeTrue()
        ->and($service->ensureResident($node, $version))->toBeEmpty();
});

it('treats a different build of the same image as absent', function () {
    [, , $node] = createServerModel();
    $storage = $node->storages()->first();
    $storage->update(['stores_import' => true]);

    fakeImportContent(["{$storage->name}:import/image-".str_repeat('b', 64).'.qcow2']);

    expect(app(ImageResidencyService::class)->isResident($node, makeVersionFor()))->toBeFalse();
});

it('names the volid qm create will import from', function () {
    [, , $node] = createServerModel();
    $storage = $node->storages()->first();
    $storage->update(['stores_import' => true]);

    $volids = app(ImageResidencyService::class)->volids($node, makeVersionFor());

    expect($volids[ImageDiskRole::SYSTEM->value])
        ->toBe("{$storage->name}:import/image-".SYSTEM_SHA.'.qcow2');
});

it('says what to fix when no storage accepts images', function () {
    [, , $node] = createServerModel();
    Storage::query()->update(['stores_import' => false]);

    // PVE keeps `import` off by default, so this is the ordinary first-run
    // state rather than a defensive branch -- worth a sentence, not a 500.
    expect(fn () => app(ImageResidencyService::class)->volids($node, makeVersionFor()))
        ->toThrow(ConflictHttpException::class, 'Add `Import` to a storage');
});
