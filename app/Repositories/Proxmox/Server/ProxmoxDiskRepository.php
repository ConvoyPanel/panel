<?php

namespace App\Repositories\Proxmox\Server;

use App\Enums\Server\DiskInterface;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
use Webmozart\Assert\Assert;

class ProxmoxDiskRepository extends ProxmoxRepository
{
    public function resizeDisk(DiskInterface $disk, int $bytes)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $kibibytes = floor($bytes / 1024);

        $response = $this->getHttpClient()
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'server' => $this->server->vmid,
                         ])
                         ->put('/api2/json/nodes/{node}/qemu/{server}/resize', [
                             'disk' => $disk->value,
                             'size' => "{$kibibytes}K",
                         ])
                         ->json();

        return $this->getData($response);
    }
}
