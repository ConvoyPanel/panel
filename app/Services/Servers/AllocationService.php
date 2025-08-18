<?php

namespace App\Services\Servers;

use App\Data\Server\Proxmox\Config\DiskData;
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
    public function syncSettings(Server $server): void
    {
        $config = $this->configRepository->setServer($server)->getConfig();

        $this->configRepository->setServer($server)->update([
            'cores' => $server->cpu,
            'memory' => $server->memory / 1024 / 1024, // convert from bytes to MiB,
        ]);

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
        // we'll be using IDE by default for now
        $ideIndex = 0; // max IDE index is '3'
        $disks = $this->getDisks($server);
        if ($disks->where('media_name', '=', $iso->name)->first()) {
            throw new IsoAlreadyMountedException;
        }

        $arrayToCheckForAvailableIdeIndex = Arr::pluck(
            $this->configRepository->setServer($server)->getConfig(),
            'key',
        );
        for ($i = 0; $i <= 4; $i++) {
            if ($i === 4) {
                throw new NoAvailableDiskInterfaceException;
            }

            if (! in_array("ide$i", $arrayToCheckForAvailableIdeIndex)) {
                $ideIndex = $i;
                break;
            }
        }

        $this->configRepository->update([
            "ide$ideIndex" => "{$server->node->iso_storage}:iso/{$iso->file_name},media=cdrom",
        ]);
    }

    public function unmountIso(Server $server, ISO $iso): void
    {
        $disks = $this->getDisks($server);
        if ($disk = $disks->where('media_name', '=', $iso->name)->first()) {
            $this->configRepository->update(['delete' => $disk->interface->value]);
        } else {
            throw new IsoAlreadyUnmountedException;
        }
    }
}
