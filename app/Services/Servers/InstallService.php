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
        $instantiatedResourceService = $this->resourceService->setServer($originalServer);
        $originalDisks = $instantiatedResourceService->getDisks();
        $originalResources = $instantiatedResourceService->getResources();

        $originalSpecifications = [
            'cores' => $originalResources['maxcpu'],
            'memory' => $originalResources['maxmem'] / 1024 / 1024,
        ];

        $this->powerService->setServer($originalServer)->kill();

        $this->delete();

        $this->setServer($template)->install($originalServer->vmid, $this->node->cluster);

        $instantiatedResourceService->setCores($originalSpecifications['cores']);
        $instantiatedResourceService->setMemory($originalSpecifications['memory']);

        // time to migrate the disks

        $newDisks = $instantiatedResourceService->getDisks();

        foreach ($originalDisks as $disk)
        {
            $existingDisk = array_search($disk['disk'], array_column($newDisks, 'disk'));

            if ($existingDisk)
            {
                // If disk exists, we'll update the size instead of creating a new disk

                // Find the size of the template VM disk and subtract it from the size of the original VM's disk to get the total size to increment in bytes
                $differenceInBytes = $this->convertToBytes($disk['size']) - $this->convertToBytes($newDisks[$existingDisk]['size']);

                $instantiatedResourceService->increaseDisk($differenceInBytes, $disk['disk']);
            } else {
                // If the disk doesn't exist, we can just create it

                $bytes = $this->convertToBytes($disk['size']);

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
