<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Proxmox\Config\DiskData;
use Convoy\Enums\Server\DiskInterface;
use Convoy\Exceptions\Service\Server\Allocation\IsoAlreadyMountedException;
use Convoy\Exceptions\Service\Server\Allocation\IsoAlreadyUnmountedException;
use Convoy\Exceptions\Service\Server\Allocation\NoAvailableDiskInterfaceException;
use Convoy\Models\ISO;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxConfigRepository;
use Illuminate\Support\Arr;
use Spatie\LaravelData\DataCollection;

class AllocationService
{
    public function __construct(protected ProxmoxConfigRepository $repository)
    {
    }

    public function syncSettings(Server $server)
    {
        return $this->updateHardware($server, $server->cpu, $server->memory);
    }

    public function getDisks(Server $server): DataCollection
    {
        $isos = $server->node->isos;

        $disks = array_values(
            array_filter($this->repository->setServer($server)->getConfig(), function ($disk) {
                return in_array($disk['key'], array_column(DiskInterface::cases(), 'value'));
            }),
        );

        return DiskData::collection(Arr::map($disks, function ($rawDisk) use ($isos, $server) {
            $disk = [
                'interface' => DiskInterface::from(Arr::get($rawDisk, 'key')),
                'is_primary_disk' => false,
                'is_media' => false,
                'media_name' => null,
                'size' => 0,
            ];

            $value = Arr::get($rawDisk, 'pending') ?? Arr::get($rawDisk, 'value');

            preg_match("/size=(\d+\w?)/s", $value, $sizeMatches);

            if (array_key_exists(1, $sizeMatches)) {
                $disk['size'] = $this->convertToBytes($sizeMatches[1]);
            }

            if (str_contains($value, 'media')) {
                $disk['is_media'] = true;
                // this piece of code adds the name of the mounted ISO
                if (preg_match("/\/(.*\.iso)/s", $value, $fileNameMatches)) {
                    if ($iso = $isos->where('file_name', $fileNameMatches[1])->first()) {
                        $disk['media_name'] = $iso->name;
                    }
                } elseif (str_contains($value, 'cloudinit')) {
                    $disk['media_name'] = 'Cloudinit';
                }
            } else {
                // if its not the ISO, we'll check if its the boot disk by comparing the size to the disk size on the eloquent record of the server
                $upperBound = $server->disk + 1024;
                $lowerBound = $server->disk - 1024;

                if ($disk['size'] < $upperBound && $disk['size'] > $lowerBound) {
                    $disk['is_primary_disk'] = true;
                }
            }

            return $disk;
        }));
    }

    public function getBootOrder(Server $server): DataCollection
    {
        $disks = $this->getDisks($server);

        $raw = collect($this->repository->setServer($server)->getConfig())->where('key', 'boot')
                                                                          ->firstOrFail();

        $untaggedDisks = array_values(
            array_filter(
                explode(';', Arr::last(explode('=', $raw['pending'] ?? $raw['value']))),
                function ($disk) {
                    return !ctype_space($disk) && in_array(
                        $disk,
                        array_column(DiskInterface::cases(), 'value'),
                    ); // filter literally whitespace entries because Proxmox keeps empty strings for some reason >:(
                },
            ),
        );

        $taggedDisks = [];

        foreach ($untaggedDisks as $untaggedDisk) {
            if ($disk = $disks->where('interface', '=', DiskInterface::from($untaggedDisk))->first(
            )) {
                array_push($taggedDisks, $disk);
            }
        }

        return DiskData::collection($taggedDisks);
    }

    public function setBootOrder(Server $server, array $disks)
    {
        return $this->repository->setServer($server)->update([
            'boot' => count($disks) > 0 ? 'order=' . Arr::join($disks, ';') : '',
        ]);
    }

    public function updateHardware(Server $server, int $cpu, int $memory)
    {
        $payload = [
            'cores' => $cpu,
            'memory' => $memory / 1048576,
        ];

        return $this->repository->setServer($server)->update($payload);
    }

    public function mountIso(Server $server, ISO $iso): void
    {
        // we'll be using IDE by default for now
        $ideIndex = 0; // max IDE index is '3'
        $disks = $this->getDisks($server);
        if ($disks->where('media_name', '=', $iso->name)->first()) {
            throw new IsoAlreadyMountedException();
        }

        $arrayToCheckForAvailableIdeIndex = Arr::pluck(
            $this->repository->setServer($server)->getConfig(),
            'key',
        );
        for ($i = 0; $i <= 4; $i++) {
            if ($i === 4) {
                throw new NoAvailableDiskInterfaceException();
            }

            if (!in_array("ide$i", $arrayToCheckForAvailableIdeIndex)) {
                $ideIndex = $i;
                break;
            }
        }

        $this->repository->update([
            "ide$ideIndex" => "{$server->node->iso_storage}:iso/{$iso->file_name},media=cdrom",
        ]);
    }

    public function unmountIso(Server $server, ISO $iso): void
    {
        $disks = $this->getDisks($server);
        if ($disk = $disks->where('media_name', '=', $iso->name)->first()) {
            $this->repository->update(['delete' => $disk->interface->value]);
        } else {
            throw new IsoAlreadyUnmountedException();
        }
    }

    public function convertToBytes(string $from): ?int
    {
        $units = ['B', 'K', 'M', 'G', 'T', 'P'];
        $number = (int)substr($from, 0, -1);
        $suffix = strtoupper(substr($from, -1));

        //B or no suffix
        if (is_numeric(substr($suffix, 0, 1))) {
            return preg_replace('/[^\d]/', '', $from);
        }

        $exponent = array_flip($units)[$suffix] ?? null;
        if ($exponent === null) {
            return null;
        }

        return $number * (1024 ** $exponent);
    }
}
