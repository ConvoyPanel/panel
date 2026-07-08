<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Config\DiskData;
use App\Enums\Server\Disk\DiskMediaType;
use App\Enums\Server\DiskInterface;
use App\Exceptions\Repository\Proxmox\RequestException;
use App\Exceptions\Service\Server\Allocation\CannotModifyPrimaryDiskException;
use App\Exceptions\Service\Server\Allocation\CannotShrinkDiskException;
use App\Exceptions\Service\Server\Allocation\IsoAlreadyMountedException;
use App\Exceptions\Service\Server\Allocation\IsoAlreadyUnmountedException;
use App\Exceptions\Service\Server\Allocation\NoAvailableDiskInterfaceException;
use App\Models\ISO;
use App\Models\Server;
use App\Models\ServerDisk;
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

    /**
     * Allocate any secondary (non-primary) data disks that aren't on the VM yet.
     *
     * Each `server_disks` row that isn't the primary is materialized as a fresh
     * Proxmox volume via the `STORAGE:SIZE_GiB` allocation syntax on the next
     * free scsi slot. Idempotent + retry-safe:
     *  - the chosen interface is persisted to the row *before* the config write,
     *    so a `ConfigureVmJob` retry reuses the same slot instead of allocating a
     *    second volume;
     *  - a disk already present on the VM (its interface appears in the live
     *    config) is skipped;
     *  - all pending disks go out in ONE digest-guarded config write (avoids a
     *    stale-digest failure between disks and stays atomic). No pending disks ⇒
     *    no empty write.
     *
     * @throws RequestException
     * @throws ConnectionException
     * @throws NoAvailableDiskInterfaceException
     */
    public function syncDisks(Server $server): void
    {
        $secondaryDisks = $server->disks()
            ->where('is_primary', false)
            ->orderBy('disk_index')
            ->get();

        if ($secondaryDisks->isEmpty()) {
            return;
        }

        $config = $this->configRepository->setServer($server)->getConfig();

        // scsi slot numbers already taken on the VM (the primary may be scsi0, or
        // on another bus entirely — we only ever place secondaries on scsi).
        $usedScsiSlots = $config->disks
            ->filter(fn (DiskData $disk) => $disk->interface->getBaseType() === 'scsi')
            ->map(fn (DiskData $disk) => $disk->interface->getSlot())
            ->values()
            ->all();

        // Assign (and persist) an interface to any disk that doesn't have one yet.
        foreach ($secondaryDisks as $disk) {
            if ($disk->interface !== null) {
                continue;
            }

            $slot = DiskInterface::getNextAvailableSlot('scsi', $usedScsiSlots);
            if ($slot === null) {
                throw new NoAvailableDiskInterfaceException;
            }

            $disk->interface = "scsi{$slot}";
            $disk->save();
            $usedScsiSlots[] = $slot;
        }

        // Build one write of the disks not already present on the VM.
        $existingInterfaces = $config->disks
            ->map(fn (DiskData $disk) => $disk->interface->value)
            ->all();

        $payload = [];
        foreach ($secondaryDisks as $disk) {
            if (in_array($disk->interface, $existingInterfaces, true)) {
                continue;
            }

            // Allocation syntax takes whole GiB; sizes are kept GiB-aligned.
            $sizeGib = max(1, (int) ceil($disk->size / (1024 ** 3)));
            $payload[$disk->interface] = "{$disk->storage->name}:{$sizeGib}";
        }

        if ($payload !== []) {
            $this->configRepository->setServer($server)->update($payload, $config->digest);
        }
    }

    /**
     * Add a secondary data disk to a server: persist the row, then allocate it
     * on Proxmox (reuses {@see syncDisks}, so slot assignment + idempotency are
     * shared). Capacity is validated by the request layer.
     *
     * @throws RequestException
     * @throws ConnectionException
     * @throws NoAvailableDiskInterfaceException
     */
    public function addDisk(Server $server, int $storageId, int $sizeBytes): ServerDisk
    {
        $disk = $server->disks()->create([
            'storage_id' => $storageId,
            'size' => $sizeBytes,
            'interface' => null,
            'is_primary' => false,
            'disk_index' => (int) $server->disks()->max('disk_index') + 1,
        ]);

        $this->syncDisks($server);

        return $disk->refresh();
    }

    /**
     * Grow a secondary disk. Proxmox resize only ever grows, so a shrink is
     * rejected up front. If the disk isn't on the VM yet (still pending build),
     * only the row is updated — the build allocates it at the new size.
     *
     * @throws RequestException
     * @throws ConnectionException
     * @throws CannotModifyPrimaryDiskException
     * @throws CannotShrinkDiskException
     */
    public function resizeDisk(Server $server, ServerDisk $disk, int $newSizeBytes): void
    {
        if ($disk->is_primary) {
            throw new CannotModifyPrimaryDiskException;
        }

        if ($newSizeBytes < $disk->size) {
            throw new CannotShrinkDiskException;
        }

        if ($newSizeBytes === $disk->size) {
            return;
        }

        if ($disk->interface !== null) {
            $config = $this->configRepository->setServer($server)->getConfig();
            $onVm = $config->disks->first(
                fn (DiskData $d) => $d->interface->value === $disk->interface,
            );

            if ($onVm !== null) {
                $this->diskRepository->setServer($server)->setDiskSize($onVm, $newSizeBytes);
            }
        }

        $disk->size = $newSizeBytes;
        $disk->save();
    }

    /**
     * Remove a secondary disk and reclaim its space. `delete=scsiN` only
     * *detaches* on Proxmox (the volume lingers as `unusedN`), so we diff the
     * raw `unused*` keys around the detach and destroy the freed volume too.
     *
     * @throws RequestException
     * @throws ConnectionException
     * @throws CannotModifyPrimaryDiskException
     */
    public function removeDisk(Server $server, ServerDisk $disk): void
    {
        if ($disk->is_primary) {
            throw new CannotModifyPrimaryDiskException;
        }

        // Never built (no interface assigned) — nothing on the VM to detach.
        if ($disk->interface === null) {
            $disk->delete();

            return;
        }

        $repository = $this->configRepository->setServer($server);

        $unusedBefore = $this->unusedKeys($repository->getRawConfig());
        $config = $repository->getConfig();

        $onVm = $config->disks->first(
            fn (DiskData $d) => $d->interface->value === $disk->interface,
        );

        if ($onVm !== null) {
            // Detach: the volume becomes an `unusedN` entry.
            $repository->update(['delete' => $disk->interface], $config->digest);

            // Destroy whatever unused slot(s) the detach created.
            $rawAfter = $repository->getRawConfig();
            $newUnused = array_diff($this->unusedKeys($rawAfter), $unusedBefore);
            foreach ($newUnused as $key) {
                $repository->update(['delete' => $key], $rawAfter['digest'] ?? null);
            }
        }

        $disk->delete();
    }

    /**
     * The `unused0`, `unused1`, … config keys present in a raw PVE config.
     *
     * @param  array<string, mixed>  $raw
     * @return list<string>
     */
    private function unusedKeys(array $raw): array
    {
        return array_values(array_filter(
            array_keys($raw),
            fn (string $key) => preg_match('/^unused\d+$/', $key) === 1,
        ));
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
