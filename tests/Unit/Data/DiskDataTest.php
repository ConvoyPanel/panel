<?php

use App\Data\Server\Proxmox\Config\DiskData;
use App\Enums\Server\Disk\DiskCacheMode;
use App\Enums\Server\Disk\DiskDiscardMode;
use App\Enums\Server\Disk\DiskFormat;
use App\Enums\Server\Disk\DiskMediaType;
use App\Enums\Server\DiskInterface;

it('parses a fully specified disk', function () {
    $disk = DiskData::fromRaw('scsi0', 'local-lvm:vm-100-disk-0,size=32G,ssd=1,discard=on,iothread=1,cache=writeback,backup=0');

    expect($disk->interface)->toBe(DiskInterface::SCSI0)
        ->and($disk->volume)->toBe('local-lvm:vm-100-disk-0')
        ->and($disk->diskMediaType)->toBe(DiskMediaType::DISK)
        ->and($disk->size)->toBe(32 * 1024 * 1024 * 1024)
        ->and($disk->format)->toBe(DiskFormat::RAW)
        ->and($disk->cacheMode)->toBe(DiskCacheMode::WRITEBACK)
        ->and($disk->discardMode)->toBe(DiskDiscardMode::ON)
        ->and($disk->isEmulatingSSD)->toBeTrue()
        ->and($disk->isIOThreadEnabled)->toBeTrue()
        ->and($disk->isIncludedInBackup)->toBeFalse();
});

it('strips a file= prefix from the volume head', function () {
    $disk = DiskData::fromRaw('scsi0', 'file=local-lvm:vm-1-disk-0,size=10G');

    expect($disk->volume)->toBe('local-lvm:vm-1-disk-0');
});

it('parses the interface from the config key', function () {
    expect(DiskData::fromRaw('virtio0', 'vol')->interface)->toBe(DiskInterface::VIRTIO0)
        ->and(DiskData::fromRaw('sata1', 'vol')->interface)->toBe(DiskInterface::SATA1)
        ->and(DiskData::fromRaw('ide0', 'vol')->interface)->toBe(DiskInterface::IDE0);
});

it('recognises a cdrom media type', function () {
    $disk = DiskData::fromRaw('scsi0', 'local:iso/debian.iso,media=cdrom,size=650M');

    expect($disk->diskMediaType)->toBe(DiskMediaType::CDROM)
        ->and($disk->volume)->toBe('local:iso/debian.iso')
        ->and($disk->size)->toBe(650 * 1024 * 1024);
});

it('scales the size unit suffix into bytes', function () {
    expect(DiskData::fromRaw('scsi0', 'vol,size=100')->size)->toBe(100)
        ->and(DiskData::fromRaw('scsi0', 'vol,size=1K')->size)->toBe(1024)
        ->and(DiskData::fromRaw('scsi0', 'vol,size=1M')->size)->toBe(1024 * 1024)
        ->and(DiskData::fromRaw('scsi0', 'vol,size=1G')->size)->toBe(1024 * 1024 * 1024)
        ->and(DiskData::fromRaw('scsi0', 'vol,size=1T')->size)->toBe(1024 * 1024 * 1024 * 1024);
});

it('reads bandwidth in raw bytes, but prefers the mbps form and scales it', function () {
    expect(DiskData::fromRaw('scsi0', 'vol,bps=1048576')->bps)->toBe(1048576)
        ->and(DiskData::fromRaw('scsi0', 'vol,mbps=1')->bps)->toBe(1048576)
        // mbps takes precedence over a co-present bps
        ->and(DiskData::fromRaw('scsi0', 'vol,mbps=2,bps=999')->bps)->toBe(2 * 1024 * 1024)
        ->and(DiskData::fromRaw('scsi0', 'vol,mbps_rd=50')->bpsRead)->toBe(50 * 1024 * 1024);
});

it('defaults backup and replicate to true when absent', function () {
    $disk = DiskData::fromRaw('scsi0', 'local-lvm:vm-1-disk-0');

    expect($disk->isIncludedInBackup)->toBeTrue()
        ->and($disk->isReplicated)->toBeTrue()
        ->and($disk->isEmulatingSSD)->toBeFalse()
        ->and($disk->isReadonly)->toBeFalse()
        ->and($disk->isIOThreadEnabled)->toBeFalse();
});

it('defaults an absent iops to 0 but an absent bps to null', function () {
    $disk = DiskData::fromRaw('scsi0', 'local-lvm:vm-1-disk-0');

    // Documented quirk: iops-family fields fall back to 0, bps-family to null.
    expect($disk->iops)->toBe(0)
        ->and($disk->iopsMax)->toBe(0)
        ->and($disk->bps)->toBeNull()
        ->and($disk->bpsMax)->toBeNull();
});

it('parses string identity fields', function () {
    $disk = DiskData::fromRaw('scsi0', 'vol,serial=ABC123,wwn=0x5000c50015ea71ad,model=Samsung');

    expect($disk->serial)->toBe('ABC123')
        ->and($disk->wwn)->toBe('0x5000c50015ea71ad')
        ->and($disk->model)->toBe('Samsung')
        ->and($disk->vendor)->toBeNull();
});
