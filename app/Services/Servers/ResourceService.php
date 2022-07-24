<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\Nodes\Information\ResourceService as InformationResourceService;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class ResourceService extends ProxmoxService
{
    private InformationResourceService $resourceService;

    public function __construct()
    {
        $this->resourceService = new InformationResourceService;
    }

    public function getResources()
    {
        $allResources = $this->resourceService->setServer($this->server)->getResourceList();

        $resources = array_search('qemu/'.$this->server->vmid, array_column($allResources, 'id'));

        return $allResources[$resources];
    }

    public function getConfig()
    {
        return $this->removeDataProperty($this->instance()->pending()->get());
    }

    public function setMemory(int $bytes)
    {
        return $this->instance()->config()->put(['memory' => $bytes]);
    }

    public function setCores(int $cores)
    {
        return $this->instance()->config()->put(['cores' => $cores]);
    }

    public function increaseDisk(int $bytes, string $disk)
    {
        return $this->instance()->resize()->put(['disk' => $disk, 'size' => '+' . $bytes . 'B']);
    }

    public function createDisk(int $bytes, string $disk, string $format = 'qcow2')
    {
        return $this->instance()->config()->post([$disk => 'local:' . ( $bytes / 1024 / 1024 / 1024) . ',format=' . $format ]);
    }

    public function parseDisk(array $disk): array
    {
        $splitSlashes = explode('/', $disk['value'] ? $disk['value'] : $disk['pending']);
        $splitCommas = explode(',', $splitSlashes[count($splitSlashes) - 1]);

        return [
            'disk' => $disk['key'],
            'size' => explode('=', $splitCommas[count($splitCommas) - 1])[1],
            'pending' => $disk['pending'] ? true : false,
        ];
    }

    // $showNonprimaryDisks if true, will show disks with NULL sizes and Cloudinit disks
    public function getDisks(bool $showNonprimaryDisks = false)
    {
        $configs = $this->getConfig();
        $disks = [];

        $diskTypes = [
            'scsi',
            'sata',
            'virtio',
            'ide',
        ];

        // not all config values are for disks
        foreach ($configs as $config)
        {
            // this checks if the config value is a disk
            foreach ($diskTypes as $diskType)
            {
                if (str_contains($config['key'], $diskType))
                {
                    $parsedDisk = $this->parseDisk($config);

                    if ($showNonprimaryDisks)
                    {
                        // show NULL disks and Cloudinit disks
                        array_push($disks, $parsedDisk);
                    } else {
                        // no matter what, it'll always return the value
                        $standardizedValue = $config['pending'] ? $config['pending'] : $config['value'];

                        if ($parsedDisk['size'] !== null && !str_contains($standardizedValue, 'cloudinit'))
                        {
                            array_push($disks, $parsedDisk);
                        }
                    }
                }
            }
        }

        return $disks;
    }
}