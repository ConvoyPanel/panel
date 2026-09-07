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
    $node->storages()->first()->update(['pve_content' => 'images,import']);

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
    $storage->update(['pve_content' => 'images,import']);

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
    $storage->update(['pve_content' => 'images,import']);

    fakeImportContent(["{$storage->name}:import/image-".str_repeat('b', 64).'.qcow2']);

    expect(app(ImageResidencyService::class)->isResident($node, makeVersionFor()))->toBeFalse();
});

it('names the volid qm create will import from', function () {
    [, , $node] = createServerModel();
    $storage = $node->storages()->first();
    $storage->update(['pve_content' => 'images,import']);

    $volids = app(ImageResidencyService::class)->volids($node, makeVersionFor());

    expect($volids[ImageDiskRole::SYSTEM->value])
        ->toBe("{$storage->name}:import/image-".SYSTEM_SHA.'.qcow2');
});

it('says what to fix when no storage accepts images', function () {
    [, , $node] = createServerModel();
    Storage::query()->update(['pve_content' => 'images']);

    // PVE keeps `import` off by default, so this is the ordinary first-run
    // state rather than a defensive branch -- worth a sentence, not a 500.
    expect(fn () => app(ImageResidencyService::class)->volids($node, makeVersionFor()))
        ->toThrow(ConflictHttpException::class, 'Add `Import` to a storage');
});

it('stores a version size in mebibytes and reads it back in bytes', function () {
    // The convention every size column follows: MiB on disk, bytes through the
    // model. Worth pinning, because this column briefly opted out of it.
    $group = ImageGroup::create(['name' => 'Sized']);
    $definition = ImageDefinition::create([
        'image_group_id' => $group->id,
        'name' => 'Sized 1.0',
        'ostype' => 'l26',
    ]);

    $definition->versions()->create([
        'version' => '1.0.0',
        'disks' => [[
            'slot' => 'scsi0',
            'role' => ImageDiskRole::SYSTEM->value,
            'url' => 'https://example.invalid/disk.qcow2',
            'path' => null,
            'sha256' => str_repeat('e', 64),
            'size' => 623145472, // 594.28 MiB
            'virtual_size' => 8589934592,
            'format' => 'qcow2',
        ]],
    ]);

    $version = $definition->versions()->sole();

    // 594 whole mebibytes on disk, and the same figure back in bytes. The lost
    // fraction of a mebibyte only ever feeds a progress bar's total.
    expect($version->getRawOriginal('size'))->toBe(594)
        ->and($version->size)->toBe(594 * 1048576);
});

it('keeps the plan floor exact rather than rounding it to whole mebibytes', function () {
    // virtual_size lives in the disks JSON, which no cast reaches into. That is
    // deliberate: understating this floor hands a tenant a larger disk than
    // they bought, silently.
    $group = ImageGroup::create(['name' => 'Exact']);
    $definition = ImageDefinition::create([
        'image_group_id' => $group->id,
        'name' => 'Exact 1.0',
        'ostype' => 'l26',
    ]);

    $version = $definition->versions()->create([
        'version' => '1.0.0',
        'disks' => [[
            'slot' => 'scsi0',
            'role' => ImageDiskRole::SYSTEM->value,
            'url' => 'https://example.invalid/disk.qcow2',
            'path' => null,
            'sha256' => str_repeat('d', 64),
            'size' => 623145472,
            // Deliberately not a whole number of mebibytes.
            'virtual_size' => 8589934593,
            'format' => 'qcow2',
        ]],
    ]);

    expect($version->fresh()->minimumDiskSize())->toBe(8589934593);
});

it('picks the newest version by its number, not by insertion order', function () {
    // The triple these sort on is derived on write. It sat at 0/0/0 for a while
    // because the listener that fills it was hung off `saving`, which the base
    // model halts -- so this pins the behaviour, not just the arithmetic.
    $group = ImageGroup::create(['name' => 'Ordered']);
    $definition = ImageDefinition::create([
        'image_group_id' => $group->id,
        'name' => 'Ordered 1.0',
        'ostype' => 'l26',
    ]);

    $disks = [[
        'slot' => 'scsi0',
        'role' => ImageDiskRole::SYSTEM->value,
        'url' => 'https://example.invalid/disk.qcow2',
        'path' => null,
        'sha256' => str_repeat('f', 64),
        'size' => 1048576,
        'virtual_size' => 1048576,
        'format' => 'qcow2',
    ]];

    // Inserted newest-first, and 1.10.0 beats 1.9.0 only if they sort as
    // integers rather than as strings.
    $definition->versions()->create(['version' => '1.10.0', 'disks' => $disks]);
    $definition->versions()->create(['version' => '1.9.0', 'disks' => $disks]);

    expect($definition->latestVersion()->version)->toBe('1.10.0');
});

it('never hands out a retired version as the latest', function () {
    $group = ImageGroup::create(['name' => 'Retired']);
    $definition = ImageDefinition::create([
        'image_group_id' => $group->id,
        'name' => 'Retired 1.0',
        'ostype' => 'l26',
    ]);

    $disks = [[
        'slot' => 'scsi0',
        'role' => ImageDiskRole::SYSTEM->value,
        'url' => 'https://example.invalid/disk.qcow2',
        'path' => null,
        'sha256' => str_repeat('9', 64),
        'size' => 1048576,
        'virtual_size' => 1048576,
        'format' => 'qcow2',
    ]];

    $definition->versions()->create(['version' => '1.0.0', 'disks' => $disks]);
    $definition->versions()->create(['version' => '2.0.0', 'disks' => $disks, 'is_active' => false]);

    expect($definition->latestVersion()->version)->toBe('1.0.0');
});
