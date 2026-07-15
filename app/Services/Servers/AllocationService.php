<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Config\DiskData;
use App\Enums\Server\Disk\DiskMediaType;
use App\Enums\Server\DiskInterface;
use App\Exceptions\Proxmox\RequestException;
use App\Exceptions\Service\Server\Allocation\CannotModifyPrimaryDiskException;
use App\Exceptions\Service\Server\Allocation\CannotShrinkDiskException;
use App\Exceptions\Service\Server\Allocation\IsoAlreadyMountedException;
use App\Exceptions\Service\Server\Allocation\IsoAlreadyUnmountedException;
use App\Exceptions\Service\Server\Allocation\NoAvailableDiskInterfaceException;
use App\Models\ISO;
use App\Models\Server;
use App\Models\ServerDisk;
use App\Services\Proxmox\Server\ProxmoxConfigClient;
use App\Services\Proxmox\Server\ProxmoxDiskClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AllocationService
{
    public function __construct(
        private ProxmoxConfigClient $configClient,
        private ProxmoxDiskClient $diskClient,
    ) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getDisks(Server $server): Collection
    {
        return $this->configClient->setServer($server)->getConfig()->disks;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getBootOrder(Server $server): Collection
    {
        return $this->configClient->setServer($server)->getConfig()->bootOrder;
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function syncSettings(Server $server): void
    {
        $config = $this->configClient->setServer($server)->getConfig();

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
            $this->configClient->setServer($server)->update($payload);
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
            $this->diskClient->setServer($server)->setDiskSize(
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

        $config = $this->configClient->setServer($server)->getConfig();

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
            $this->configClient->setServer($server)->update($payload, $config->digest);
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
            $config = $this->configClient->setServer($server)->getConfig();
            $onVm = $config->disks->first(
                fn (DiskData $d) => $d->interface->value === $disk->interface,
            );

            if ($onVm !== null) {
                $this->diskClient->setServer($server)->setDiskSize($onVm, $newSizeBytes);
            }
        }

        $disk->size = $newSizeBytes;
        $disk->save();
    }

    /**
     * How many times to re-read the config looking for the detached volume to
     * surface as `unusedN`, and how long to wait between reads (µs).
     *
     * On a *running* VM `delete=scsiN` only schedules the hot-unplug: the API
     * call returns before QEMU confirms it, and the volume moves to `unusedN`
     * a moment later — so an immediate single re-read races it and misses the
     * freed volume (leaving it orphaned on disk). We poll instead. On a stopped
     * VM the entry is there on the first read, so the loop exits immediately.
     */
    private const UNUSED_POLL_ATTEMPTS = 12;

    private const UNUSED_POLL_DELAY_US = 500_000;

    /**
     * Remove a secondary disk and reclaim its space. `delete=scsiN` only
     * *detaches* on Proxmox (the volume lingers as `unusedN`); we then destroy
     * that freed volume so nothing is left orphaned on the storage.
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

        $client = $this->configClient->setServer($server);

        $config = $client->getConfig();
        $onVm = $config->disks->first(
            fn (DiskData $d) => $d->interface->value === $disk->interface,
        );

        if ($onVm !== null) {
            // Detach: the volume becomes an `unusedN` entry (async on a running
            // VM — see the poll constants). Match on the exact volume id, not a
            // before/after count, so a concurrent unused slot can't fool us.
            $client->update(['delete' => $disk->interface], $config->digest);
            $this->purgeDetachedVolume($client, $onVm->volume);
        }

        $disk->delete();
    }

    /**
     * Poll the raw config until the just-detached volume surfaces as an
     * `unusedN` key, then delete that key to destroy the underlying volume.
     * Best-effort: if it never appears within the window we stop (the disk is
     * already detached; a leftover volume is preferable to blocking forever).
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    private function purgeDetachedVolume(ProxmoxConfigClient $client, string $volume): void
    {
        for ($attempt = 0; $attempt < self::UNUSED_POLL_ATTEMPTS; ++$attempt) {
            $raw = $client->getRawConfig();
            $key = $this->findUnusedKeyForVolume($raw, $volume);

            if ($key !== null) {
                $client->update(['delete' => $key], $raw['digest'] ?? null);

                return;
            }

            usleep(self::UNUSED_POLL_DELAY_US);
        }
    }

    /**
     * The `unusedN` config key whose value references $volume, if any. PVE
     * renders the unused entry as the bare volume id (no `,size=` suffix), so
     * compare on the volume id portion only.
     *
     * @param  array<string, mixed>  $raw
     */
    private function findUnusedKeyForVolume(array $raw, string $volume): ?string
    {
        foreach ($raw as $key => $value) {
            if (preg_match('/^unused\d+$/', $key) !== 1) {
                continue;
            }

            if (is_string($value) && explode(',', $value)[0] === $volume) {
                return $key;
            }
        }

        return null;
    }

    public function setBootOrder(Server $server, array $disks)
    {
        return $this->configClient->setServer($server)->update([
            'boot' => count($disks) > 0 ? 'order='.Arr::join($disks, ';') : '',
        ]);
    }

    public function mountIso(Server $server, ISO $iso): void
    {
        // One read tells us everything: whether the ISO is already mounted,
        // which IDE slot is free, and the digest to guard the write with (a
        // concurrent mount could otherwise claim the same slot).
        $config = $this->configClient->setServer($server)->getConfig();

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

        $this->configClient->update([
            "ide$ideIndex" => $this->isoVolume($iso).',media=cdrom',
        ], $config->digest);
    }

    public function unmountIso(Server $server, ISO $iso): void
    {
        // Read the full config (not just the disks) so we can guard the delete
        // with its digest — the interface we delete is derived from this read.
        $config = $this->configClient->setServer($server)->getConfig();

        $disk = $this->findMountedIsoDisk($config->disks, $iso);

        if ($disk === null) {
            throw new IsoAlreadyUnmountedException;
        }

        $this->configClient->update(['delete' => $disk->interface->value], $config->digest);
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
