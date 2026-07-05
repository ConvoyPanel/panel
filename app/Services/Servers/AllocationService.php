<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Config\DiskData;
use App\Enums\Server\Disk\DiskMediaType;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Exceptions\Service\Server\Allocation\IsoAlreadyMountedException;
use App\Exceptions\Service\Server\Allocation\IsoAlreadyUnmountedException;
use App\Exceptions\Service\Server\Allocation\NoAvailableDiskInterfaceException;
use App\Models\ISO;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use App\Repositories\Proxmox\Server\ProxmoxDiskRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AllocationService
{
    public function __construct(
        private ProxmoxConfigRepository $configRepository,
        private ProxmoxDiskRepository $diskRepository,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getDisks(Server $server): Collection
    {
        return $this->configRepository->setServer($server)->getConfig()->disks;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getBootOrder(Server $server): Collection
    {
        return $this->configRepository->setServer($server)->getConfig()->bootOrder;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function syncSettings(Server $server): void
    {
        $config = $this->configRepository->setServer($server)->getConfig();

        // Only push cores/memory that actually differ, so an unchanged sync doesn't
        // enqueue a redundant Proxmox "Configure" task. $config->memory is in bytes;
        // PVE's payload wants integer MiB.
        $desiredMemoryMib = (int) ($server->memory / 1024 / 1024);
        $currentMemoryMib = (int) ($config->memory / 1024 / 1024);

        $payload = [];
        if ($config->cpu->coreCount !== $server->cpu) {
            $payload['cores'] = $server->cpu;
        }
        if ($currentMemoryMib !== $desiredMemoryMib) {
            $payload['memory'] = $desiredMemoryMib;
        }

        if ($payload !== []) {
            $this->configRepository->setServer($server)->update($payload);
        }

        // We're assuming the largest disk is the disk to be resized.
        /** @var ?DiskData $disk */
        $disk = $config->disks->reduce(function (?DiskData $carry, DiskData $disk) {
            if ($carry === null || $disk->size > $carry->size) {
                return $disk;
            }

            return $carry;
        });

        if ($disk !== null && $server->disk > $disk->size) {
            $this->diskRepository->setServer($server)->setDiskSize(
                $disk,
                $server->disk,
            );
        }
    }

    public function setBootOrder(Server $server, array $disks)
    {
        return $this->configRepository->setServer($server)->update([
            'boot' => count($disks) > 0 ? 'order='.Arr::join($disks, ';') : '',
        ]);
    }

    public function mountIso(Server $server, ISO $iso): void
    {
        // One read tells us everything: whether the ISO is already mounted,
        // which IDE slot is free, and the digest to guard the write with (a
        // concurrent mount could otherwise claim the same slot).
        $config = $this->configRepository->setServer($server)->getConfig();

        if ($this->findMountedIsoDisk($config->disks, $iso)) {
            throw new IsoAlreadyMountedException;
        }

        $ideIndex = 0; // max IDE index is '3'
        $usedKeys = $config->disks
            ->map(fn (DiskData $disk) => $disk->interface->value)
            ->all();
        for ($i = 0; $i <= 4; $i++) {
            if ($i === 4) {
                throw new NoAvailableDiskInterfaceException;
            }

            if (! in_array("ide$i", $usedKeys)) {
                $ideIndex = $i;
                break;
            }
        }

        $this->configRepository->update([
            "ide$ideIndex" => $this->isoVolume($iso).',media=cdrom',
        ], $config->digest);
    }

    public function unmountIso(Server $server, ISO $iso): void
    {
        // Read the full config (not just the disks) so we can guard the delete
        // with its digest — the interface we delete is derived from this read.
        $config = $this->configRepository->setServer($server)->getConfig();

        $disk = $this->findMountedIsoDisk($config->disks, $iso);

        if ($disk === null) {
            throw new IsoAlreadyUnmountedException;
        }

        $this->configRepository->update(['delete' => $disk->interface->value], $config->digest);
    }

    /**
     * The Proxmox volume string a mounted copy of this ISO takes, e.g.
     * "local:iso/debian-12.iso" — the same value {@see mountIso} writes.
     */
    private function isoVolume(ISO $iso): string
    {
        return "{$iso->storage->name}:iso/{$iso->file_name}";
    }

    /**
     * Find the cdrom disk this ISO is mounted on, if any. Matches on the
     * backing volume (not a non-existent "media_name"), so it correctly
     * identifies the mount instead of never matching.
     *
     * @param  \Illuminate\Support\Collection<int, DiskData>  $disks
     */
    private function findMountedIsoDisk(Collection $disks, ISO $iso): ?DiskData
    {
        $volume = $this->isoVolume($iso);

        return $disks->first(fn (DiskData $disk) => $disk->diskMediaType === DiskMediaType::CDROM
            && $disk->volume === $volume);
    }
}
