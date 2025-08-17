<?php

namespace App\Repositories\Proxmox\Server;

use App\Enums\Server\DiskInterface;
use App\Repositories\Proxmox\ProxmoxRepository;

class ProxmoxDiskRepository extends ProxmoxRepository
{
    public function resizeDisk(DiskInterface $disk, int $bytes)
    {
        $kibibytes = floor($bytes / 1024);

        $response = $this->getHttpClientWithParams()
            ->put('/api2/json/nodes/{node}/qemu/{server}/resize', [
                'disk' => $disk->value,
                'size' => "{$kibibytes}K",
            ])
            ->json();

        return $this->getData($response);
    }
}
