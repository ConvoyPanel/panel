<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Models\Server;
use Webmozart\Assert\Assert;
use Convoy\Enums\Server\DiskInterface;
use Convoy\Repositories\Proxmox\ProxmoxRepository;

class ProxmoxDiskRepository extends ProxmoxRepository
{
    public function resizeDisk(DiskInterface $disk, int $bytes)
    {
        Assert::isInstanceOf($this->server, Server::class);

        $gigabytes = $bytes / 1073741824;

        $response = $this->getHttpClient()
            ->withUrlParameters([
                'node' => $this->node->cluster,
                'server' => $this->server->vmid,
            ])
            ->put('/api2/json/nodes/{node}/qemu/{server}/resize', [
                'disk' => $disk->value,
                'size' => "+{$gigabytes}G",
            ])
            ->json();

        return $this->getData($response);
    }
}
