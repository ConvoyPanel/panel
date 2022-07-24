<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Services\ProxmoxService;

/**
 * Class SnapshotService
 * @package App\Services\Servers
 */
class InstallService extends ProxmoxService
{
    private PowerService $powerService;
    private ResourceService $resourceService;

    public function __construct()
    {
        $this->powerService = new PowerService();
        $this->resourceService = new ResourceService();
    }

    public function install(int $newid, string $target)
    {
        return $this->instance()->clone()->post(['newid' => $newid, 'target' => $target, 'full' => true]);
    }

    public function delete(bool $destroyUnreferencedDisks = true, bool $purgeJobConfigurations = true)
    {
        $this->powerService->setServer($this->server)->kill();

        return $this->instance()->delete(['destroy-unreferenced-disks' => $destroyUnreferencedDisks, 'purge' => $purgeJobConfigurations]);
    }

    public function reinstall(Server $template)
    {
        $originalServer = clone $this->server;
        $instantiatedResourceService = (clone $this->resourceService)->setServer($originalServer);
        $originalDisks = $instantiatedResourceService->getDisks();
        $originalResources = $instantiatedResourceService->getResources();
        $templateDisks = (clone $this->resourceService)->setServer($template)->getDisks();

        $originalSpecifications = [
            'cores' => $originalResources['maxcpu'],
            'memory' => $originalResources['maxmem'] / 1024 / 1024,
        ];

        (clone $this->powerService)->setServer($originalServer)->kill();

        $this->delete();

        $this->setServer($template)->install($originalServer->vmid, $this->node->cluster);

        $instantiatedResourceService->setCores($originalSpecifications['cores']);
        $instantiatedResourceService->setMemory($originalSpecifications['memory']);

        // time to migrate the disks

        $newDisks = [];

        // If the template has no disks. I don't want to get stuck in an infinite loop waiting for a disk to appear.
        if (count($templateDisks) > 0) {
            do {
                $newDisks = $instantiatedResourceService->getDisks();
            } while (count($newDisks) === 0);
        }


        foreach ($originalDisks as $disk) {
            $existingDisk = array_search($disk['disk'], array_column($newDisks, 'disk'));
/*
            if (!$existingDisk)
            {
                dd([$newDisks, $originalDisks, $existingDisk, array_column($newDisks, 'disk'),  array_search('woww',  array_column($newDisks, 'disk'))]);
            } */

            if ($existingDisk !== false) {
                // If disk exists, we'll update the size instead of creating a new disk

                // Find the size of the template VM disk and subtract it from the size of the original VM's disk to get the total size to increment in bytes
                $differenceInBytes = $this->convertToBytes($disk['size']) - $this->convertToBytes($newDisks[$existingDisk]['size']);

                //dd([$newDisks, $originalDisks, $disk, $existingDisk]);

                $instantiatedResourceService->increaseDisk($differenceInBytes, $disk['disk']);
            } else {
                // If the disk doesn't exist, we can just create it

                $bytes = $this->convertToBytes($disk['size']);

                //dd([$newDisks, $originalDisks, $disk, $existingDisk]);

                $instantiatedResourceService->createDisk($bytes, $disk['disk']);
            }
        }

        // apply the changes
        $this->powerService->setServer($originalServer)->kill();

        return true;
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
