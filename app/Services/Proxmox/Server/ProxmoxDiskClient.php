<?php

namespace App\Services\Proxmox\Server;

use App\Data\Server\Proxmox\Config\DiskData;
use App\Services\Proxmox\ProxmoxClient;

class ProxmoxDiskClient extends ProxmoxClient
{
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
