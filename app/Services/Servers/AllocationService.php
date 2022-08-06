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

    public function getDisks()
    {
        Assert::isInstanceOf($this->server, Server::class);

        $this->repository->setServer($this->server);

        $disks = array_values(array_filter($this->repository->getAllocations(), function ($disk) {
            return in_array($disk['key'], $this->repository->validDisks);
        }));

        return Arr::map($disks, function ($value) {
            return $this->formatDisk($value);
        });
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