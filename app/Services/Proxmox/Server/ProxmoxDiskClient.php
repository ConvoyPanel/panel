<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\Config\DiskData;
use App\Services\Proxmox\ProxmoxClient;
use App\Services\Servers\DiskResizeService;

class ProxmoxDiskClient extends ProxmoxClient
{
    /**
     * Grow a disk to an absolute size.
     *
     * PVE forks a worker for this and answers with its UPID, so a successful
     * call means only that the resize was accepted -- see {@see DiskResizeService},
     * which is what callers should go through rather than this directly.
     *
     * @return mixed the task UPID, or null on a node that resized inline
     */
    public function setDiskSize(DiskData $disk, int $bytes)
    {
        $kibibytes = floor($bytes / 1024);

        $response = $this->getHttpClientWithParams()
            ->put('/api2/json/nodes/{node}/qemu/{server}/resize', [
                'disk' => $disk->interface->value,
                'size' => "{$kibibytes}K",
            ])
            ->json();

        return $this->getData($response);
    }
}
