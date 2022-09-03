<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use App\Services\ProxmoxService;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class AllocationService extends ProxmoxService
{
    private ProxmoxAllocationRepository $repository;

    public function __construct()
    {
        $this->repository = new ProxmoxAllocationRepository;
    }

    public function createDisk(int $bytes, string $disk, string $format = 'qcow2')
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($format, $this->repository->diskFormats, 'Invalid disk format');
        Assert::inArray($disk, $this->repository->validDisks, 'Invalid disk type');

        return $this->repository->setServer($this->server)->update([
            $disk => 'local:' . ($bytes / 11073741824) . ',format=' . $format
        ]);
    }

    /*
    * @deprecated This function is redundant and is just a proxy to another function
    */
    public function resizeDisk(int $bytes, string $disk)
    {
        Assert::isInstanceOf($this->server, Server::class);

        return $this->repository->setServer($this->server)->resizeDisk($bytes, $disk);
    }

    public function getDisks(): array
    {
        Assert::isInstanceOf($this->server, Server::class);

        $disks = array_values(array_filter($this->repository->setServer($this->server)->getAllocations(), function ($disk) {
            return in_array($disk['key'], $this->repository->validDisks);
        }));

        return Arr::map($disks, function ($value) {
            return $this->formatDisk($value);
        });
    }

    public function getBootOrder(bool $filterNonLocalDisks = false): array
    {
        Assert::isInstanceOf($this->server, Server::class);

        $raw = collect($this->repository->setServer($this->server)->getAllocations())->where('key', 'boot')->firstOrFail();

        $disks = array_values(array_filter(explode(';', Arr::last(explode('=', Arr::get($raw, 'value')))), function ($disk) {
            return !ctype_space($disk); // filter literally whitespace entries because Proxmox keeps empty strings for some reason >:(
        }));

        if ($filterNonLocalDisks)
            return array_values(array_filter($disks, function ($disk) {
                return in_array($disk, $this->repository->validDisks);
            }));

        return $disks;
    }

    public function setBootOrder(array $disks)
    {
        Assert::isInstanceOf($this->server, Server::class);

        return $this->repository->setServer($this->server)->update([
            'boot' => count($disks) > 0 ? 'order=' . Arr::join($disks, ';') : ''
        ]);
    }

    public function updateSpecifications(array $specs)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $payload = [];

        if (Arr::exists($specs, 'cpu'))
            $payload['cores'] = Arr::get($specs, 'cpu');
        if (Arr::exists($specs, 'memory'))
            $payload['memory'] = Arr::get($specs, 'memory') / 1048576;

        return $this->repository->setServer($this->server)->update($payload);
    }

    public function formatDisk(array $rawDisk): array
    {
        $disk = [
            'disk' => Arr::get($rawDisk, 'key'),
            'size' => 0,
            'pending' => Arr::exists($rawDisk, 'pending'),
        ];

        $diskProperty = Arr::last(explode(',', Arr::get($rawDisk, 'value')));

        $disk['size'] = $this->convertToBytes(Arr::last(explode('=', $diskProperty)));

        return $disk;
    }

    public function convertToBytes(string $from): ?int
    {
        $units = ['B', 'K', 'M', 'G', 'T', 'P'];
        $number = substr($from, 0, -1);
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