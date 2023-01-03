<?php

namespace Convoy\Services\Servers;

use Convoy\Data\Server\Proxmox\Config\DiskData;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class AllocationService extends ProxmoxService
{
    public function __construct(protected ProxmoxAllocationRepository $repository)
    {
    }

    public function createDisk(int $bytes, string $disk, string $format = 'qcow2')
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($format, ProxmoxAllocationRepository::$diskFormats, 'Invalid disk format');
        Assert::inArray($disk, ProxmoxAllocationRepository::$validDisks, 'Invalid disk type');

        return $this->repository->setServer($this->server)->update([
            $disk => 'local:' . ($bytes / 11073741824) . ',format=' . $format,
        ]);
    }

    public function getDisks(Server $server)
    {
        $isos = $server->node->isos;

        $disks = array_values(array_filter($this->repository->setServer($server)->getAllocations(), function ($disk) {
            return in_array($disk['key'], ProxmoxAllocationRepository::$validDisks);
        }));

        return DiskData::collection(Arr::map($disks, function ($rawDisk) use ($isos, $server) {
            $disk = [
                'name' => Arr::get($rawDisk, 'key'),
                'size' => 0,
            ];

            $value = Arr::get($rawDisk, 'pending') ?? Arr::get($rawDisk, 'value');

            preg_match("/size=(\d+\w?)/s", $value, $sizeMatches);

            $disk['size'] = $this->convertToBytes($sizeMatches[1]);

            if (str_contains($value, 'media')) {
                // this piece of code adds the name of the mounted ISO
                if (preg_match("/\/(.*\.iso)/s", $value, $fileNameMatches)) {
                    if ($iso = $isos->where('file_name', $fileNameMatches[1])->first()) {
                        $disk['display_name'] = $iso->name;
                    }
                }
            } else {
                // if its not the ISO, we'll check if its the boot disk by comparing the size to the disk size on the eloquent record of the server
                $upperBound = $server->disk + 1024;
                $lowerBound = $server->disk - 1024;

                if ($disk['size'] < $upperBound && $disk['size'] > $lowerBound) {
                    $disk['display_name'] = 'Primary';
                }
            }

            return $disk;
        }));
    }

    public function getBootOrder(Server $server)
    {
        $disks = $this->getDisks($server);

        $raw = collect($this->repository->setServer($server)->getAllocations())->where('key', 'boot')->firstOrFail();

        $untaggedDisks = array_values(array_filter(explode(';', Arr::last(explode('=', $raw['pending'] ?? $raw['value']))), function ($disk) {
            return !ctype_space($disk) && in_array($disk, ProxmoxAllocationRepository::$validDisks); // filter literally whitespace entries because Proxmox keeps empty strings for some reason >:(
        }));

        $taggedDisks = [];

        foreach ($untaggedDisks as $untaggedDisk) {
            if ($disk = $disks->where('name', '=', $untaggedDisk)->first()) {
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
            'memory' => $memory / 1048576
        ];

        return $this->repository->setServer($server)->update($payload);
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
